<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
use App\Models\chart_of_account;
use App\Models\tax_master;

class item extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'uom_id',
        // purchasing side (RFD lines)
        'gl_account_id',
        'default_tax_id',
        'default_unit_price',
        'purchase_uom_id',
        // selling side (sales invoice lines) - added by 2026_11_10_000003
        'is_sellable',
        'sales_uom_id',
        'sales_gl_account_id',
        'default_sales_tax_id',
        'default_sales_price',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'default_unit_price' => 'decimal:2',
        'default_sales_price' => 'decimal:2',
        'is_sellable' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

    public function glAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

    public function defaultTax()
    {
        return $this->belongsTo(tax_master::class, 'default_tax_id');
    }

    public function salesGlAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'sales_gl_account_id');
    }

    public function defaultSalesTax()
    {
        return $this->belongsTo(tax_master::class, 'default_sales_tax_id');
    }

    public function rfdDetails()
    {
        return $this->hasMany(rfd_detail::class, 'item_id');
    }

    public function salesInvoiceLines()
    {
        return $this->hasMany(sales_invoice_line::class, 'item_id');
    }

    public function unit_of_measure()
    {
        return $this->belongsTo(unit_of_measure::class, 'uom_id');
    }

    /** Alias for unit_of_measure() — matches what invoice/RFD line code uses. */
    public function uom()
    {
        return $this->belongsTo(unit_of_measure::class, 'uom_id');
    }

    /** Selling-side unit (falls back to the base unit when not set — see formData()). */
    public function salesUom()
    {
        return $this->belongsTo(unit_of_measure::class, 'sales_uom_id');
    }
}