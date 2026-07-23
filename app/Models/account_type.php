<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class account_type extends Model
{
    protected $table = 'account_types';

    protected $fillable = [
        'code',
        'description',
        'range',
        'created_by',
        'updated_by',
    ];
}
