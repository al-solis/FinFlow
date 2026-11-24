<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sales_invoice_line_tax extends Model
{
    protected $table = 'sales_invoice_line_taxes';

    protected $fillable = [
        'sales_invoice_line_id', 'line_no', 'tax_id', 'taxable_amount', 'tax_amount', 'created_by', 'updated_by',
    ];

    public function line()
    {
        return $this->belongsTo(sales_invoice_line::class, 'sales_invoice_line_id');
    }

    public function tax()
    {
        return $this->belongsTo(tax_master::class, 'tax_id');
    }
}
