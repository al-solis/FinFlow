<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\unit_of_measure;
use App\Models\sales_invoice;
use App\Models\item;
use App\Models\chart_of_account;
use App\Models\sales_invoice_line_tax;

class sales_invoice_line extends Model
{
    protected $table = 'sales_invoice_lines';
    protected $guarded = [];

    public function invoice()
    {
        return $this->belongsTo(sales_invoice::class, 'sales_invoice_id');
    }

    public function item()
    {
        return $this->belongsTo(item::class, 'item_id');
    }

    public function glAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

    public function taxes()
    {
        return $this->hasMany(sales_invoice_line_tax::class, 'sales_invoice_line_id');
    }

    /** Same contract as rfd_detail::recalcTotals(): call after syncing taxes(). */
    public function recalcTotals(): void
    {
        $taxable = round(((float) $this->quantity * (float) $this->unit_price) - (float) $this->discount_amount, 2);
        $tax = round((float) $this->taxes()->sum('tax_amount'), 2);

        $this->taxable_amount = $taxable;
        $this->tax_amount = $tax;
        $this->total_amount = round($taxable + $tax, 2);
        $this->save();
    }

    public function uom()
    {
        return $this->belongsTo(unit_of_measure::class, 'uom_id');
    }
}
