<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class module extends Model
{
    protected $table = 'modules';

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'path',
        'src',
        'sequence',
        'is_active',
    ];

    public function accessRights()
    {
        return $this->hasMany(access_right::class);
    }
}
