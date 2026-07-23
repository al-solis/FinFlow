<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class account_structure extends Model
{
    protected $table = 'account_structures';

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_default',
        'status',
        'created_by',
        'updated_by',
    ];
}
