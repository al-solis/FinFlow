<?php

namespace App\Services;

use App\Models\approval_workflow;
use App\Models\approval_transaction;
use App\Models\approval_transaction_history;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApprovalWorkflowService
{
    /**
     * Start the approval flow for a freshly submitted document.
     * e.g. ApprovalWorkflowService::submit($rfdHeader, 'rfd', $rfdHeader->total_due);
     */
    public function submit(Model $document, string $moduleCode, float $amount): approval_transaction
    {
        $workflow = approval_workflow::resolveFor($moduleCode, $amount, $document->organization_id ?? null);

        if (! $workflow) {
            throw new RuntimeException("No active approval workflow configured for module [{$moduleCode}] at this amount.");
        }

        return DB::transaction(function () use ($document, $workflow) {
            $transaction = approval_transaction::create([
                'approval_workflow_id' => $workflow->id,
                'approvable_type'      => get_class($document),
                'approvable_id'        => $document->id,
                'current_step_no'      => 1,
                'status'               => 'in_progress',
                'created_by'           => Auth::id(),
            ]);

            approval_transaction_history::create([
                'approval_transaction_id' => $transaction->id,
                'step_no'                 => 1,
                'action'                  => 'submitted',
                'actor_id'                => Auth::id(),
                'acted_at'                => now(),
                'created_by'              => Auth::id(),
            ]);

            $document->forceFill(['approval_status' => 'in_progress'])->save();

            return $transaction;
        });
    }

    /**
     * Approve the current step. Advances to the next step, or closes out
     * the transaction as fully approved if this was the final step.
     */
    public function approve(approval_transaction $transaction, ?string $remarks = null): approval_transaction
    {
        $step = $transaction->currentStep();

        if (! $step) {
            throw new RuntimeException('Current step could not be resolved for this transaction.');
        }

        return DB::transaction(function () use ($transaction, $step, $remarks) {
            approval_transaction_history::create([
                'approval_transaction_id'   => $transaction->id,
                'approval_workflow_step_id' => $step->id,
                'step_no'                   => $step->step_no,
                'action'                    => 'approved',
                'actor_id'                  => Auth::id(),
                'remarks'                   => $remarks,
                'acted_at'                  => now(),
                'created_by'                => Auth::id(),
            ]);

            if ($step->is_final_approval) {
                $transaction->update([
                    'status'     => 'approved',
                    'updated_by' => Auth::id(),
                ]);

                // Release the document downstream, e.g. to payment processing.
                $document = $transaction->approvable;
                $document->forceFill([
                    'approval_status' => 'approved',
                    'status'          => 'for_processing', // adjust to match rfd_headers.status values
                    'approved_by'     => Auth::id(),
                ])->save();
            } else {
                $nextStep = $transaction->workflow->steps()
                    ->where('step_no', '>', $step->step_no)
                    ->orderBy('step_no')
                    ->first();

                $transaction->update([
                    'current_step_no' => $nextStep->step_no,
                    'status'          => 'in_progress',
                    'updated_by'      => Auth::id(),
                ]);
            }

            return $transaction->refresh();
        });
    }

    /**
     * Reject outright — stops the flow, document goes back to draft/rejected.
     */
    public function reject(approval_transaction $transaction, string $remarks): approval_transaction
    {
        return DB::transaction(function () use ($transaction, $remarks) {
            $step = $transaction->currentStep();

            approval_transaction_history::create([
                'approval_transaction_id'   => $transaction->id,
                'approval_workflow_step_id' => $step?->id,
                'step_no'                   => $transaction->current_step_no,
                'action'                    => 'rejected',
                'actor_id'                  => Auth::id(),
                'remarks'                   => $remarks,
                'acted_at'                  => now(),
                'created_by'                => Auth::id(),
            ]);

            $transaction->update(['status' => 'rejected', 'updated_by' => Auth::id()]);
            $transaction->approvable->forceFill(['approval_status' => 'rejected'])->save();

            return $transaction->refresh();
        });
    }

    /**
     * Send the document back to the requester for correction, restarting at step 1.
     */
    public function returnToRequester(approval_transaction $transaction, string $remarks): approval_transaction
    {
        return DB::transaction(function () use ($transaction, $remarks) {
            $step = $transaction->currentStep();

            approval_transaction_history::create([
                'approval_transaction_id'   => $transaction->id,
                'approval_workflow_step_id' => $step?->id,
                'step_no'                   => $transaction->current_step_no,
                'action'                    => 'returned',
                'actor_id'                  => Auth::id(),
                'remarks'                   => $remarks,
                'acted_at'                  => now(),
                'created_by'                => Auth::id(),
            ]);

            $transaction->update(['status' => 'returned', 'current_step_no' => 1, 'updated_by' => Auth::id()]);
            $transaction->approvable->forceFill(['approval_status' => 'returned'])->save();

            return $transaction->refresh();
        });
    }

    /**
     * Whether the currently authenticated approver may edit the chart of
     * account / tax lines while the document sits on this step.
     */
    public function permissionsFor(approval_transaction $transaction): array
    {
        $step = $transaction->currentStep();

        return [
            'can_edit_coa'    => (bool) ($step->can_edit_chart_of_account ?? false),
            'can_edit_tax'    => (bool) ($step->can_edit_tax ?? false),
            'can_edit_amount' => (bool) ($step->can_edit_amount ?? false),
            'can_return'      => (bool) ($step->can_return_to_requester ?? false),
            'is_final_step'   => (bool) ($step->is_final_approval ?? false),
        ];
    }
}
