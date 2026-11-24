<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class credit_memo_line_tax extends Model
{
    protected $table = 'credit_memo_line_taxes';

    protected $fillable = [
        'credit_memo_line_id',
        'line_no',
        'tax_id',
        'taxable_amount',
        'tax_amount',
        'created_by',
        'updated_by',
    ];

    public function line()
    {
        return $this->belongsTo(credit_memo_line::class, 'credit_memo_line_id');
    }

    public function tax()
    {
        return $this->belongsTo(tax_master::class, 'tax_id');
    }
}