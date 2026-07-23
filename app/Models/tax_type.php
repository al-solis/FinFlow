<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tax_type extends Model
{
    protected $table = 'tax_types';

    protected $fillable = [
        'code',
        'name',
        'created_by',
        'updated_by'
    ];
}
