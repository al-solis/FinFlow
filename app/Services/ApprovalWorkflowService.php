<?php

namespace App\Services;

use RuntimeException;
use App\Notifications\ApprovalStepNotification;
use App\Services\Concerns\PostsAccountsReceivable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\approval_workflow;
use App\Models\approval_transaction;
use App\Models\approval_transaction_history;
use App\Models\CashAdvance;
use App\Models\CashAdvanceLiquidation;
use App\Models\CashAdvanceRefund;
use App\Models\rfd_header;
use App\Models\User;
use App\Models\gl_journal;
use App\Models\sales_invoice;
use App\Models\collection;
use App\Models\credit_memo;
use App\Models\debit_memo;

class ApprovalWorkflowService
{
    use PostsAccountsReceivable;
    public function __construct(
        protected AccountingService $accounting,
    ) {
    }

    /**
     * Submit a request for approval
     */
    public function submit(Model $approvable, string $moduleCode, float $amount, int $submittedBy): approval_transaction
    {
        $workflow = approval_workflow::resolveFor($moduleCode, $amount, $approvable->organization_id ?? null);

        if (!$workflow || $workflow->steps->where('is_active', true)->isEmpty()) {
            throw new RuntimeException("No active approval workflow configured for [{$moduleCode}] at this amount.");
        }

        return DB::transaction(function () use ($workflow, $approvable, $submittedBy) {
            $firstStep = $workflow->steps->where('is_active', true)->first();

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
                'status' => $this->statusFor($approvable, '1'),
            ])->save();

            $this->notifyStepApprovers($tx, $firstStep);

            return $tx;
        });
    }

    /**
     * Approve a request
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

                $approvable->refresh();

                $approvable->forceFill([
                    'approval_status' => '2',
                    'status' => $this->statusFor($approvable, '2'),
                    'approved_by' => $actorId,
                ])->save();

                $approvable->refresh();

                // Post accounting entries based on module type
                $this->postAccountingForModule($approvable, $actorId);

                return $tx;
            }

            // Move to next step
            $nextStep = $tx->workflow->steps
                ->where('step_no', '>', $step->step_no)
                ->where('is_active', true)
                ->sortBy('step_no')
                ->first();

            if (!$nextStep) {
                // No more steps - auto approve
                $tx->update(['status' => 'approved']);
                $approvable = $tx->approvable;
                $approvable->forceFill([
                    'approval_status' => '2',
                    'status' => $this->statusFor($approvable, '2'),
                    'approved_by' => $actorId,
                ])->save();

                // Post accounting entries based on module type
                $this->postAccountingForModule($approvable, $actorId);

                return $tx;
            }

            $tx->update(['current_step_no' => $nextStep->step_no]);
            $this->notifyStepApprovers($tx, $nextStep);

            return $tx;
        });
    }

    /**
     * Return request to requester
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
                'approval_status' => '4',
                'status' => $this->statusFor($approvable, '0'),
            ])->save();

            $requesterId = $this->getRequesterId($approvable);
            if ($requesterId && ($requester = User::find($requesterId))) {
                $requester->notify(new ApprovalStepNotification($tx, $step, 'returned', $remarks));
            }

            return $tx;
        });
    }

    /**
     * Reject a request
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

            $approvable = $tx->approvable;
            $approvable->forceFill([
                'approval_status' => '3',
                'status' => $this->statusFor($approvable, '3'),
            ])->save();

            return $tx;
        });
    }

    /**
     * Value for the approvable's own `status` column while it moves through approval.
     * gl_journal and collection use named statuses ('draft' -> 'posted'/'voided'), not the
     * numeric ones, so the workflow must not write '1'/'2'/'3' into them: the collection's
     * `status` is what the posted/voided checks and the list filters read.
     */
    protected function statusFor(Model $approvable, string $numeric): string
    {
        /**
         * Value for the approvable's own `status` column while it moves through approval.
         *
         * gl_journal and collection use named statuses ('draft' -> 'posted'/'voided');
         * credit and debit memos use the numeric map (0=Draft, 1=Submitted, 2=Approved,
         * 3=Rejected, 4=Returned) and must receive the numeric value unchanged.
         */
        // These two share named statuses; the numeric memo map does not apply.
        if ($approvable instanceof gl_journal || $approvable instanceof collection) {
            return 'draft';
        }

        // Credit and debit memos use the numeric map (0..4) — return it untouched.
        return $numeric;
    }

    /**
     * Get the requester ID from the approvable model
     */
    protected function getRequesterId(Model $approvable): ?int
    {
        return match (true) {
            $approvable instanceof rfd_header => $approvable->created_by,
            $approvable instanceof CashAdvance => $approvable->employee_id ?? $approvable->created_by,
            $approvable instanceof CashAdvanceLiquidation => $approvable->employee_id ?? $approvable->created_by,
            $approvable instanceof CashAdvanceRefund => $approvable->employee_id ?? $approvable->created_by,
            $approvable instanceof sales_invoice => $approvable->created_by,
            $approvable instanceof collection => $approvable->created_by,
            $approvable instanceof credit_memo => $approvable->created_by,
            $approvable instanceof debit_memo => $approvable->created_by,
            default => $approvable->created_by ?? null,
        };
    }

    /**
     * Post accounting entries based on the module type
     */
    protected function postAccountingForModule(Model $approvable, int $actorId): void
    {
        match (true) {
            $approvable instanceof rfd_header => $this->accounting->postRfdApproval($approvable->fresh(), $actorId),
            $approvable instanceof CashAdvance => $this->accounting->postCashAdvanceApproval($approvable->fresh(), $actorId),
            $approvable instanceof CashAdvanceLiquidation => $this->accounting->postLiquidationApproval($approvable->fresh(), $actorId),
            $approvable instanceof CashAdvanceRefund => $this->handleRefundReimbursementApproval($approvable, $actorId),
            $approvable instanceof gl_journal => $this->handleJournalApproval($approvable, $actorId),
            $approvable instanceof sales_invoice => $this->accounting->postSalesInvoiceApproval($approvable->fresh(), $actorId),
            $approvable instanceof collection => $this->postCollectionOnApproval($approvable->fresh(), $actorId),
            $approvable instanceof credit_memo => $this->postCreditMemoOnApproval($approvable->fresh(), $actorId),
            $approvable instanceof debit_memo => $this->postDebitMemoOnApproval($approvable->fresh(), $actorId),
            default => null,
        };
    }

    protected function handleJournalApproval(gl_journal $journal, int $actorId): void
    {
        $journal->update([
            'approval_status' => '2',
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $actorId,
            'approved_by' => $actorId,
        ]);
    }

    /**
     * Handle refund/reimbursement approval separately
     * Refund and Reimbursement should create GL entries on approval
     */
    protected function handleRefundReimbursementApproval(CashAdvanceRefund $approvable, int $actorId): void
    {
        if ($approvable->type === 'refund') {
            $this->accounting->postRefundApproval($approvable->fresh(), $actorId);
        } elseif ($approvable->type === 'reimbursement') {
            $this->accounting->postReimbursementApproval($approvable->fresh(), $actorId);
        }
    }

    protected function postCollectionOnApproval(collection $collection, int $actorId): void
    {
        $applications = $collection->applications->map(fn($a) => [
            'sales_invoice_id' => $a->sales_invoice_id,
            'amount_applied' => (float) $a->amount_applied,
            'cwt_applied' => (float) $a->cwt_applied,
        ])->all();

        $this->accounting->postCollection($collection, $applications, $actorId);
    }

    protected function postCreditMemoOnApproval(credit_memo $memo, int $actorId): void
    {
        $this->accounting->postCreditMemoApproval($memo, $actorId);
    }

    protected function postDebitMemoOnApproval(debit_memo $memo, int $actorId): void
    {
        $this->accounting->postDebitMemoApproval($memo, $actorId);
    }

    /**
     * Notify step approvers
     */
    protected function notifyStepApprovers(approval_transaction $tx, $step): void
    {
        if ($step->approver_type === 'user' && $step->user_id) {
            $step->user?->notify(new ApprovalStepNotification($tx, $step, 'pending_approval'));
            return;
        }

        if ($step->approver_type === 'role' && $step->role_id) {
            $users = User::where('role_id', $step->role_id)->get();
            foreach ($users as $user) {
                $user->notify(new ApprovalStepNotification($tx, $step, 'pending_approval'));
            }
        }
    }

    /**
     * Get the current step for a transaction
     */
    public function getCurrentStep(approval_transaction $tx)
    {
        return $tx->currentStep();
    }

    /**
     * Check if a transaction is pending approval
     */
    public function isPending(approval_transaction $tx): bool
    {
        return $tx->status === 'pending';
    }

    /**
     * Check if a transaction is approved
     */
    public function isApproved(approval_transaction $tx): bool
    {
        return $tx->status === 'approved';
    }

    /**
     * Check if a transaction is rejected
     */
    public function isRejected(approval_transaction $tx): bool
    {
        return $tx->status === 'rejected';
    }

    /**
     * Check if a transaction is returned
     */
    public function isReturned(approval_transaction $tx): bool
    {
        return $tx->status === 'returned';
    }
}