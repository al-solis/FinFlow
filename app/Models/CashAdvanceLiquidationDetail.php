<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAdvanceLiquidationDetail extends Model
{
    protected $table = 'cash_advance_liquidation_details';

    protected $fillable = [
        'liquidation_id',
        'expense_date',
        'description',
        'gl_account_id',
        'amount',
        'reference',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function liquidation(): BelongsTo
    {
        return $this->belongsTo(CashAdvanceLiquidation::class, 'liquidation_id');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

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

    /**
     * Get the formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }
}