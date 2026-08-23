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

class ApprovalWorkflowService
{
    public function __construct(protected AccountingService $accounting)
    {
    }

    public function submit(Model $approvable, string $moduleCode, float $amount, int $submittedBy): approval_transaction
    {
        $workflow = approval_workflow::resolveFor($moduleCode, $amount, $approvable->organization_id ?? null);

        if (!$workflow || $workflow->steps->isEmpty()) {
            throw new RuntimeException("No active approval workflow configured for [{$moduleCode}] at this amount.");
        }

        return DB::transaction(function () use ($workflow, $approvable, $submittedBy) {
            $firstStep = $workflow->steps->first();

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
                'approval_status' => '1',
                'status' => '1',
            ])->save();

            $this->notifyStepApprovers($tx, $firstStep);

            return $tx;
        });
    }

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
                    'approval_status' => '2',
                    'status' => '2',
                    'approved_by' => $actorId,
                ])->save();

                if ($approvable instanceof \App\Models\rfd_header) {
                    $this->accounting->postRfdApproval($approvable->fresh(), $actorId);
                }

                return $tx;
            }

            $nextStep = $tx->workflow->steps
                ->where('step_no', '>', $step->step_no)
                ->sortBy('step_no')
                ->first();

            if (!$nextStep) {
                $tx->update(['status' => 'approved']);
                $approvable = $tx->approvable;
                $approvable->forceFill(['approval_status' => '2', 'status' => '2'])->save();

                if ($approvable instanceof \App\Models\rfd_header) {
                    $this->accounting->postRfdApproval($approvable->fresh(), $actorId);
                }

                return $tx;
            }

            $tx->update(['current_step_no' => $nextStep->step_no]);
            $this->notifyStepApprovers($tx, $nextStep);

            return $tx;
        });
    }

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
                'approval_status' => '4',
                'status' => '0',
            ])->save();

            $requesterId = $approvable->created_by ?? null;
            if ($requesterId && ($requester = User::find($requesterId))) {
                $requester->notify(new ApprovalStepNotification($tx, $step, 'returned', $remarks));
            }

            return $tx;
        });
    }

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
            User::role($step->role_id)
                ->get()
                ->each(fn(User $user) => $user->notify(new ApprovalStepNotification($tx, $step, 'pending_approval')));
        }
    }
}