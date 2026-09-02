<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReimbursementDetail extends Model
{
    protected $table = 'reimbursement_details';

    protected $fillable = [
        'reimbursement_id',
        'expense_date',
        'description',
        'gl_account_id',
        'amount',
        'reference',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the reimbursement this detail belongs to.
     */
    public function reimbursement(): BelongsTo
    {
        return $this->belongsTo(CashAdvanceRefund::class, 'reimbursement_id');
    }

    /**
     * Get the GL account for this expense.
     */
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

    /**
     * Get the creator of this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater of this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }
}