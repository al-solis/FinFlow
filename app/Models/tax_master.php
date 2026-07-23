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
        'created_by',
        'updated_by'
    ];
}
