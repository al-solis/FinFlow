<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class country extends Model
{
    protected $table = 'countries';

    protected $fillable = [
        'code',
        'name',
        'iso2',
        'iso3',
        'currency_code',
        'dial_code',
        'status',
        'created_by',
        'updated_by',
    ];
}
