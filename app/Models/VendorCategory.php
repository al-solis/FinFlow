<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorCategory extends Model
{
    protected $table = 'vendor_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];
}
