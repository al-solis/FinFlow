<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class credit_memo_line extends Model
{
    protected $table = 'credit_memo_lines';
    protected $guarded = [];

    public function memo()
    {
        return $this->belongsTo(credit_memo::class, 'credit_memo_id');
    }

    public function item()
    {
        return $this->belongsTo(item::class, 'item_id');
    }

    public function glAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

    public function uom()
    {
        return $this->belongsTo(unit_of_measure::class, 'uom_id');
    }

    public function taxes()
    {
        return $this->hasMany(credit_memo_line_tax::class, 'credit_memo_line_id');
    }

    /** Recalculate this line's totals from its taxes (call after syncing taxes). */
    public function recalcTotals(): void
    {
        $taxable = round(((float) $this->quantity * (float) $this->unit_price) - (float) $this->discount_amount, 2);
        $tax = round((float) $this->taxes()->sum('tax_amount'), 2);

        $this->taxable_amount = $taxable;
        $this->tax_amount = $tax;
        $this->total_amount = round($taxable + $tax, 2);
        $this->save();
    }
}