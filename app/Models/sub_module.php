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
        'group',
        'icon',
        'img',
        'src',
        'sequence',
        'is_active',
    ];

    public function module()
    {
        return $this->belongsTo(module::class);
    }

    public function getRouteNameAttribute()
    {
        return strtolower(str_replace('-', '.', $this->code));
    }
}
