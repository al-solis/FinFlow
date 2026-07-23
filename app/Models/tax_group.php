<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tax_group extends Model
{
    protected $table = 'tax_groups';

    protected $fillable = [
        'code',
        'name',
        'created_by',
        'updated_by'
    ];
}
