<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\approval_workflow;
use App\Models\approval_transaction_history;
use App\Models\User;
use App\Models\rfd_header;
use App\Models\CashAdvance;
use App\Models\CashAdvanceLiquidation;
use App\Models\CashAdvanceRefund;
use App\Models\gl_journal;
use App\Models\credit_memo;
use App\Models\debit_memo;

class approval_transaction extends Model
{
    protected $table = 'approval_transactions';

    protected $fillable = [
        'approval_workflow_id',
        'approvable_type',
        'approvable_id',
        'current_step_no',
        'status',
        'created_by',
        'updated_by',
    ];

    public function workflow()
    {
        return $this->belongsTo(approval_workflow::class, 'approval_workflow_id');
    }

    public function approvable()
    {
        return $this->morphTo();
    }

    public function histories()
    {
        return $this->hasMany(approval_transaction_history::class, 'approval_transaction_id')
            ->orderBy('created_at');
    }

    public function currentStep()
    {
        return $this->workflow->steps()->where('step_no', $this->current_step_no)->first();
    }

    /**
     * Get the review URL for this transaction
     */
    public function reviewUrl(): string
    {
        return match ($this->approvable_type) {
            rfd_header::class => route('ap.rfd.showApproval', $this->id),
            CashAdvance::class => route('cm.ca.showApproval', [$this->approvable_id, $this->id]),
            CashAdvanceLiquidation::class => route('cm.liquidation.showApproval', [$this->approvable_id, $this->id]),
            CashAdvanceRefund::class => $this->approvable?->type === 'reimbursement'
                ? route('cm.reimbursement.showApproval', [$this->approvable_id, $this->id])
                : route('cm.refund.showApproval', [$this->approvable_id, $this->id]),
            gl_journal::class => route('gl.journal.showApproval', [$this->approvable_id, $this->id]),
            sales_invoice::class => route('ar.invoices.showApproval', [$this->approvable_id, $this->id]),
            collection::class => route('ar.collections.showApproval', [$this->approvable_id, $this->id]),
            credit_memo::class => route('ar.credit-memos.showApproval', [$this->approvable_id, $this->id]),
            debit_memo::class => route('ar.debit-memos.showApproval', [$this->approvable_id, $this->id]),
            default => '#',
        };
    }

    /**
     * Get the module type label
     */
    public function getModuleLabel(): string
    {
        $map = [
            rfd_header::class => 'RFD',
            CashAdvance::class => 'Cash Advance',
            CashAdvanceLiquidation::class => 'Liquidation',
            gl_journal::class => 'Journal Entry',
            CashAdvanceRefund::class => $this->approvable?->type === 'refund' ? 'Refund' : 'Reimbursement',
            sales_invoice::class => 'Sales Invoice',
            collection::class => 'Collection',
            credit_memo::class => 'Credit Memo',
            debit_memo::class => 'Debit Memo',
        ];

        return $map[$this->approvable_type] ?? 'Unknown';
    }

    /**
     * Get the module badge class
     */
    public function getModuleBadgeClass(): string
    {
        if ($this->approvable_type === CashAdvanceRefund::class) {
            return $this->approvable?->type === 'refund'
                ? 'bg-orange-50 text-orange-700'
                : 'bg-teal-50 text-teal-700';
        }

        $map = [
            rfd_header::class => 'bg-blue-50 text-blue-700',
            CashAdvance::class => 'bg-purple-50 text-purple-700',
            CashAdvanceLiquidation::class => 'bg-green-50 text-green-700',
            gl_journal::class => 'bg-indigo-50 text-indigo-700',
            sales_invoice::class => 'bg-yellow-50 text-yellow-700',
            collection::class => 'bg-emerald-50 text-emerald-700',
            credit_memo::class => 'bg-rose-50 text-rose-700',
            debit_memo::class => 'bg-amber-50 text-amber-700',
        ];

        return $map[$this->approvable_type] ?? 'bg-gray-50 text-gray-700';
    }

    /**
     * Get the reference number for display
     */
    public function getReferenceNumber(): string
    {
        $prefix = match ($this->approvable_type) {
            rfd_header::class => 'RFD',
            CashAdvance::class => 'CA',
            CashAdvanceLiquidation::class => 'LIQ',
            CashAdvanceRefund::class => $this->approvable?->type === 'reimbursement' ? 'REIMB' : 'REF',
            gl_journal::class => 'JRN',
            sales_invoice::class => 'INV',
            collection::class => 'COL',
            credit_memo::class => 'CM',
            debit_memo::class => 'DM',
            default => 'REQ',
        };

        return $prefix . '-' . str_pad($this->approvable_id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the requestor name
     */
    public function getRequestorName(): string
    {
        $approvable = $this->approvable;

        if (!$approvable) {
            return 'Unknown';
        }

        $creator = match ($this->approvable_type) {
            rfd_header::class => $approvable->creator,
            CashAdvance::class => $approvable->employee,
            CashAdvanceLiquidation::class => $approvable->employee,
            CashAdvanceRefund::class => $approvable->employee,
            gl_journal::class => $approvable->creator,
            sales_invoice::class => $approvable->creator,
            collection::class => $approvable->creator,
            credit_memo::class => $approvable->creator,
            debit_memo::class => $approvable->creator,
            default => null,
        };

        if (!$creator) {
            return 'Unknown';
        }

        return trim(($creator->last_name ?? '') . ', ' . ($creator->first_name ?? '') . ' ' . ($creator->middle_name ?? ''));
    }

    /**
     * Get the requestor email
     */
    public function getRequestorEmail(): string
    {
        $approvable = $this->approvable;

        if (!$approvable) {
            return '';
        }

        $creator = match ($this->approvable_type) {
            rfd_header::class => $approvable->creator,
            CashAdvance::class => $approvable->employee,
            CashAdvanceLiquidation::class => $approvable->employee,
            CashAdvanceRefund::class => $approvable->employee,
            gl_journal::class => $approvable->creator,
            sales_invoice::class => $approvable->creator,
            collection::class => $approvable->creator,
            credit_memo::class => $approvable->creator,
            debit_memo::class => $approvable->creator,
            default => null,
        };

        return $creator?->email ?? '';
    }

    /**
     * Get the total amount
     */
    public function getTotalAmount(): float
    {
        $approvable = $this->approvable;

        if (!$approvable) {
            return 0;
        }

        return match ($this->approvable_type) {
            rfd_header::class => (float) $approvable->total_due,
            CashAdvance::class => (float) $approvable->amount,
            CashAdvanceLiquidation::class => (float) $approvable->total_expenses,
            CashAdvanceRefund::class => (float) $approvable->amount,
            sales_invoice::class => (float) $approvable->total_amount,
            collection::class => (float) $approvable->amount_received,
            credit_memo::class => (float) $approvable->total_amount,
            debit_memo::class => (float) $approvable->total_amount,
            default => 0,
        };
    }

    /**
     * Get the currency symbol
     */
    public function getCurrencySymbol(): string
    {
        $approvable = $this->approvable;

        if (!$approvable) {
            return '₱';
        }

        return match ($this->approvable_type) {
            rfd_header::class => $approvable->currency?->symbol ?? '₱',
            sales_invoice::class => $approvable->currency?->symbol ?? '₱',
            collection::class => $approvable->currency?->symbol ?? '₱',
            credit_memo::class => $approvable->currency?->symbol ?? '₱',
            debit_memo::class => $approvable->currency?->symbol ?? '₱',
            default => '₱',
        };
    }

    /**
     * Get the purpose/remarks
     */
    public function getPurpose(): string
    {
        $approvable = $this->approvable;

        if (!$approvable) {
            return '—';
        }

        return match ($this->approvable_type) {
            rfd_header::class => $approvable->remarks ?? '—',
            CashAdvance::class => $approvable->purpose ?? '—',
            CashAdvanceLiquidation::class => $approvable->remarks ?? '—',
            CashAdvanceRefund::class => $approvable->purpose ?? '—',
            sales_invoice::class => $approvable->remarks ?? '—',
            collection::class => $approvable->remarks ?? '—',
            credit_memo::class => $approvable->remarks ?: ($approvable->reason ?? '—'),
            debit_memo::class => $approvable->remarks ?: ($approvable->reason ?? '—'),
            default => '—',
        };
    }

    public function scopePendingFor($query, User $user)
    {
        return $query->where('approval_transactions.status', 'pending')
            ->join('approval_workflow_steps', function ($join) {
                $join->on('approval_workflow_steps.approval_workflow_id', '=', 'approval_transactions.approval_workflow_id')
                    ->on('approval_workflow_steps.step_no', '=', 'approval_transactions.current_step_no');
            })
            ->where(function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('approval_workflow_steps.approver_type', 'user')
                        ->where('approval_workflow_steps.user_id', $user->id);
                })->orWhere(function ($q2) use ($user) {
                    $q2->where('approval_workflow_steps.approver_type', 'role')
                        ->where('approval_workflow_steps.role_id', $user->role_id);
                });
            })
            ->select('approval_transactions.*');
    }

    public function isPendingFor(User $user): bool
    {
        return static::pendingFor($user)->whereKey($this->getKey())->exists();
    }
}