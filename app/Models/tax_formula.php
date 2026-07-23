<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tax_formula extends Model
{
    protected $table = 'tax_formulas';

    protected $fillable = [
        'code',
        'name',
        'type',
        'basis',
        'operation',
        'expression',
        'rounding',
        'decimal_places',
        'status',
        'created_by',
        'updated_by'
    ];
}
