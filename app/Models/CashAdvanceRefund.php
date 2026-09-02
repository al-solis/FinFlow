<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\gl_journal;
use App\Models\chart_of_account;


class CashAdvanceRefund extends Model
{
    protected $table = 'cash_advance_refunds';

    protected $fillable = [
        'organization_id',
        'cash_advance_id',
        'liquidation_id',
        'employee_id',
        'amount',
        'purpose',
        'gl_account_id',
        'type',
        'status',
        'approval_status',
        'approved_by',
        'submitted_at',
        'submitted_by',
        'paid_at',
        'paid_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function cashAdvance(): BelongsTo
    {
        return $this->belongsTo(CashAdvance::class, 'cash_advance_id');
    }

    public function liquidation(): BelongsTo
    {
        return $this->belongsTo(CashAdvanceLiquidation::class, 'liquidation_id');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ReimbursementDetail::class, 'reimbursement_id');
    }

    public function approvalTransactions()
    {
        return $this->morphMany(approval_transaction::class, 'approvable');
    }

    public function latestApprovalTransaction()
    {
        return $this->morphOne(approval_transaction::class, 'approvable')->latestOfMany();
    }

    public function apInvoices()
    {
        return $this->morphMany(ap_invoice::class, 'source', 'source_type', 'source_id');
    }

    public function glJournals(): MorphMany
    {
        return $this->morphMany(
            gl_journal::class,
            'reference',
            'reference_type',
            'reference_id'
        );
    }

    public function totalReimbursementDisbursedAmount(): float
    {
        return (float) $this->glJournals()
            ->where('organization_id', $this->organization_id)
            ->where('status', 'posted')
            ->sum('total_debit');
    }

    public function isFromLiquidation(): bool
    {
        return !is_null($this->liquidation_id);
    }

    public function statusLabel(): string
    {
        return match ($this->approval_status) {
            '2' => 'Approved',
            '1' => 'Pending Approval',
            '4' => 'Returned',
            '3' => 'Rejected',
            '5' => 'Paid',
            default => 'Draft',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->approval_status) {
            '2' => 'bg-green-100 text-green-700',
            '1' => 'bg-yellow-100 text-yellow-700',
            '4' => 'bg-orange-100 text-orange-700',
            '3' => 'bg-red-100 text-red-700',
            '5' => 'bg-blue-100 text-blue-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'refund' => 'Refund (Employee → Company)',
            'reimbursement' => 'Reimbursement (Company → Employee)',
            default => '—',
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'refund' => 'bg-purple-100 text-purple-700',
            'reimbursement' => 'bg-indigo-100 text-indigo-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    public function isReimbursement(): bool
    {
        return $this->type === 'reimbursement';
    }

    public function isPaid(): bool
    {
        return $this->status === '5'; //|| !is_null($this->paid_at);
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }

    public function scopeReimbursements($query)
    {
        return $query->where('type', 'reimbursement');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', '1');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', '2');
    }

    public function scopePaid($query)
    {
        return $query->where('status', '5');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', '5');
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    public function markAsPaid(int $userId): void
    {
        $this->update([
            'status' => '5',
            'paid_at' => now(),
            'paid_by' => $userId,
        ]);
    }
}