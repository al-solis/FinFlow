<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;

class tax_type extends Model
{
    protected $table = 'tax_types';

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by'
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }
}
