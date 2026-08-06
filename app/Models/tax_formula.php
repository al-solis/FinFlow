<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
class tax_formula extends Model
{
    protected $table = 'tax_formulas';

    protected $fillable = [
        'organization_id',
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

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }
}
