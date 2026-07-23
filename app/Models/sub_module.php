<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sub_module extends Model
{
    protected $table = 'sub_modules';

    protected $fillable = [
        'module_id',
        'code',
        'name',
        'description',
        'icon',
        'img',
        'sequence',
        'is_active',
    ];

    public function module()
    {
        return $this->belongsTo(module::class);
    }
}
