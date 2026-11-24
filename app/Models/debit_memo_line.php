<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class debit_memo_line extends Model
{
    protected $table = 'debit_memo_lines';
    protected $guarded = [];

    public function memo()
    {
        return $this->belongsTo(debit_memo::class, 'debit_memo_id');
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
        return $this->hasMany(debit_memo_line_tax::class, 'debit_memo_line_id');
    }

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