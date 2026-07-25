<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tax_master extends Model
{
    protected $table = 'tax_masters';

    protected $fillable = [
        'code',
        'name',
        'tax_type_id',
        'tax_formula_id',
        'rate',
        'fixed_amount',
        'gl_account_code',
        // 'gl_account_id',
        'recoverable',
        'priority',
        'effective_from',
        'effective_to',
        'status',
        'created_by',
        'updated_by'
    ];

    public function taxType()
    {
        return $this->belongsTo(tax_type::class, 'tax_type_id');
    }

    public function taxFormula()
    {
        return $this->belongsTo(tax_formula::class, 'tax_formula_id');
    }
}
