<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class currency extends Model
{
    protected $table = 'currencies';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'status',
        'created_by',
        'updated_by',
    ];
}
