<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CashAdvance;
use App\Models\CashAdvanceLiquidationDetail;
use App\Models\CashAdvanceLiquidationAttachment;

class CashAdvanceLiquidation extends Model
{
    protected $table = 'cash_advance_liquidations';

    protected $fillable = [
        'organization_id',
        'cash_advance_id',
        'employee_id',
        'liquidation_date',
        'total_expenses',
        'credit_account_id',
        'remarks',
        'status', // 0=draft, 1=pending, 2=approved, 3=rejected, 4=returned
        'approval_status',
        'approved_by',
        'submitted_at',
        'submitted_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'liquidation_date' => 'date',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_expenses' => 'decimal:2',
    ];

    public function cashAdvance(): BelongsTo
    {
        return $this->belongsTo(CashAdvance::class, 'cash_advance_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'credit_account_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(CashAdvanceLiquidationDetail::class, 'liquidation_id');
    }

    public function approvalTransactions()
    {
        return $this->morphMany(approval_transaction::class, 'approvable');
    }

    public function latestApprovalTransaction()
    {
        return $this->morphOne(approval_transaction::class, 'approvable')->latestOfMany();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reimbursement()
    {
        return $this->hasOne(CashAdvanceRefund::class, 'liquidation_id')->where('type', 'reimbursement');
    }

    public function hasExcess(): bool
    {
        $ca = $this->cashAdvance;
        $remaining = (float) $ca->disbursed_amount - (float) $ca->liquidated_amount;
        return $this->total_expenses > $remaining;
    }

    public function getExcessAmount(): float
    {
        $ca = $this->cashAdvance;
        $remaining = (float) $ca->disbursed_amount - (float) $ca->liquidated_amount;
        return max(0, $this->total_expenses - $remaining);
    }

    public function attachments()
    {
        return $this->hasMany(CashAdvanceLiquidationAttachment::class, 'liquidation_id');
    }

    public function statusLabel(): string
    {
        return match ($this->approval_status) {
            '2' => 'Approved',
            '1' => 'Pending Approval',
            '4' => 'Returned',
            '3' => 'Rejected',
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
            default => 'bg-gray-100 text-gray-600',
        };
    }
}