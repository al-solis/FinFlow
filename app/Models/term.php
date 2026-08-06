<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class term extends Model
{
    protected $table = 'terms';

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'days',
        'status',
        'created_by',
        'updated_by',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }
}
