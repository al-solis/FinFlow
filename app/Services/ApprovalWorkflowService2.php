<?php

namespace App\Services;

use App\Models\approval_workflow;
use App\Models\approval_transaction;
use App\Models\approval_transaction_history;
use App\Notifications\ApprovalStepNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reusable engine for driving any approvable model (rfd_header, payment_request, etc.)
 * through its matching approval_workflow. The approvable model just needs
 * `approval_status`, `status`, and `organization_id` columns.
 */
class ApprovalWorkflowService
{
    /**
     * Resolve the right workflow for this module/amount and kick off the
     * transaction at step 1, notifying the first approver(s).
     */

    public function submit(Model $approvable, string $moduleCode, float $amount, int $submittedBy): approval_transaction
    {
        $workflow = approval_workflow::resolveFor($moduleCode, $amount, $approvable->organization_id ?? null);

        if (!$workflow || $workflow->steps->isEmpty()) {
            throw new RuntimeException("No active approval workflow configured for [{$moduleCode}] at this amount.");
        }

        return DB::transaction(function () use ($workflow, $approvable, $submittedBy) {
            $firstStep = $workflow->steps->first();

            // dd($firstStep, $approvable, $submittedBy);

            $tx = approval_transaction::create([
                'approval_workflow_id' => $workflow->id,
                'approvable_type' => $approvable->getMorphClass(),
                'approvable_id' => $approvable->getKey(),
                'current_step_no' => $firstStep->step_no,
                'status' => 'pending',
                'created_by' => $submittedBy,
            ]);

            approval_transaction_history::create([
                'approval_transaction_id' => $tx->id,
                'approval_workflow_step_id' => null,
                'step_no' => 0,
                'action' => 'submitted',
                'actor_id' => $submittedBy,
                'acted_at' => now(),
                'created_by' => $submittedBy,
            ]);

            $approvable->forceFill([
                'approval_status' => '1', //pending
                'status' => '1', //submitted
            ])->save();

            $this->notifyStepApprovers($tx, $firstStep);

            return $tx;
        });
    }

    /**
     * Approve the current step. Advances to the next step, or finalizes the
     * transaction if this was the final approval.
     */
    public function approve(approval_transaction $tx, int $actorId, ?string $remarks = null): approval_transaction
    {
        $step = $tx->currentStep();

        if (!$step) {
            throw new RuntimeException('Current step could not be resolved for this transaction.');
        }

        return DB::transaction(function () use ($tx, $step, $actorId, $remarks) {
            approval_transaction_history::create([
                'approval_transaction_id' => $tx->id,
                'approval_workflow_step_id' => $step->id,
                'step_no' => $step->step_no,
                'action' => 'approved',
                'actor_id' => $actorId,
                'remarks' => $remarks,
                'acted_at' => now(),
                'created_by' => $actorId,
            ]);

            if ($step->is_final_approval) {
                $tx->update(['status' => 'approved']);

                $approvable = $tx->approvable;
                $approvable->forceFill([
                    'approval_status' => '2', // approved
                    'status' => '2', // approved and ready to be picked up by disbursement processing
                    'approved_by' => $actorId,
                ])->save();

                return $tx;
            }

            $nextStep = $tx->workflow->steps
                ->where('step_no', '>', $step->step_no)
                ->sortBy('step_no')
                ->first();

            if (!$nextStep) {
                // Safety net: no further step defined but this wasn't flagged final.
                $tx->update(['status' => 'approved']);
                $tx->approvable->forceFill(['approval_status' => '2', 'status' => '2'])->save();
                return $tx;
            }

            $tx->update(['current_step_no' => $nextStep->step_no]);
            $this->notifyStepApprovers($tx, $nextStep);

            return $tx;
        });
    }

    /**
     * Send the transaction back to the original requester for corrections.
     * Only allowed if the current step permits it.
     */
    public function returnToRequester(approval_transaction $tx, int $actorId, string $remarks): approval_transaction
    {
        $step = $tx->currentStep();

        if (!$step || !$step->can_return_to_requester) {
            throw new RuntimeException('This approval step is not permitted to return the request.');
        }

        return DB::transaction(function () use ($tx, $step, $actorId, $remarks) {
            approval_transaction_history::create([
                'approval_transaction_id' => $tx->id,
                'approval_workflow_step_id' => $step->id,
                'step_no' => $step->step_no,
                'action' => 'returned',
                'actor_id' => $actorId,
                'remarks' => $remarks,
                'acted_at' => now(),
                'created_by' => $actorId,
            ]);

            $tx->update(['status' => 'returned']);

            $approvable = $tx->approvable;
            $approvable->forceFill([
                'approval_status' => '4', // returned
                'status' => '0', // draft
            ])->save();

            $requesterId = $approvable->created_by ?? null;
            if ($requesterId && ($requester = User::find($requesterId))) {
                $requester->notify(new ApprovalStepNotification($tx, $step, 'returned', $remarks));
            }

            return $tx;
        });
    }

    /**
     * Hard reject — ends the workflow rather than sending it back for edits.
     */
    public function reject(approval_transaction $tx, int $actorId, string $remarks): approval_transaction
    {
        $step = $tx->currentStep();

        return DB::transaction(function () use ($tx, $step, $actorId, $remarks) {
            approval_transaction_history::create([
                'approval_transaction_id' => $tx->id,
                'approval_workflow_step_id' => $step->id ?? null,
                'step_no' => $step->step_no ?? $tx->current_step_no,
                'action' => 'rejected',
                'actor_id' => $actorId,
                'remarks' => $remarks,
                'acted_at' => now(),
                'created_by' => $actorId,
            ]);

            $tx->update(['status' => 'rejected']);
            $tx->approvable->forceFill(['approval_status' => '3', 'status' => '3'])->save();

            return $tx;
        });
    }

    protected function notifyStepApprovers(approval_transaction $tx, $step): void
    {
        if ($step->approver_type === 'user' && $step->user_id) {
            $step->user?->notify(new ApprovalStepNotification($tx, $step, 'pending_approval'));
            return;
        }

        if ($step->approver_type === 'role' && $step->role_id) {
            User::role($step->role_id) // adjust to your actual role-scoping method (e.g. spatie/permission)
                ->get()
                ->each(fn(User $user) => $user->notify(new ApprovalStepNotification($tx, $step, 'pending_approval')));
        }
    }
}
