<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class term extends Model
{
    protected $table = 'terms';

    protected $fillable = [
        'code',
        'name',
        'description',
        'days',
        'status',
        'created_by',
        'updated_by',
    ];
}
