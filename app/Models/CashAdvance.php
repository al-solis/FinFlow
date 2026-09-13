<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\CashAdvanceLiquidation;
use App\Models\approval_transaction;
use App\Models\chart_of_account;

class CashAdvance extends Model
{
    protected $table = 'cash_advances';

    protected $fillable = [
        'organization_id',
        'employee_id',
        'amount',
        'disbursed_amount',
        'gl_account_id',
        'purpose',
        'expected_liquidation_date',
        'status', // 0=draft, 1=pending, 2=approved, 3=rejected, 4=returned, 5=disbursed, 6=fully liquidated
        'approval_status', // 0=draft, 1=pending, 2=approved, 3=rejected, 4=returned
        'liquidated_amount',
        'approved_by',
        'submitted_at',
        'submitted_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'liquidated_amount' => 'decimal:2',
        'expected_liquidation_date' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function liquidations()
    {
        return $this->hasMany(CashAdvanceLiquidation::class, 'cash_advance_id');
    }

    public function refunds()
    {
        return $this->hasMany(CashAdvanceRefund::class, 'cash_advance_id')->where('type', 'refund');
    }

    public function reimbursements()
    {
        return $this->hasMany(CashAdvanceRefund::class, 'cash_advance_id')->where('type', 'reimbursement');
    }

    public function approvalTransactions()
    {
        return $this->morphMany(approval_transaction::class, 'approvable');
    }

    public function latestApprovalTransaction()
    {
        return $this->morphOne(approval_transaction::class, 'approvable')
            ->latestOfMany();
    }

    public function glAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

    public function apInvoices()
    {
        return $this->morphMany(ap_invoice::class, 'source', 'source_type', 'source_id');
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->amount - (float) ($this->disbursed_amount ?? 0);
    }

    public function isFullyDisbursed(): bool
    {
        return (float) ($this->disbursed_amount ?? 0) >= (float) $this->amount;
    }

    public function statusLabel(): string
    {
        return match ($this->approval_status) {
            '2' => 'Approved',
            '1' => 'Pending Approval',
            '4' => 'Returned',
            '3' => 'Rejected',
            '5' => 'Disbursed',
            '6' => 'Fully Liquidated',
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
            '6' => 'bg-green-100 text-green-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }
}