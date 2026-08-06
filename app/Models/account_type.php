<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
class account_type extends Model
{
    protected $table = 'account_types';

    protected $fillable = [
        'organization_id',
        'code',
        'description',
        'range',
        'created_by',
        'updated_by',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }
}
