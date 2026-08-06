<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Monolog\Handler\RollbarHandler;
use App\Models\organization;
use App\Models\role;
use App\Models\module;
use App\Models\sub_module;

class access_right extends Model
{
    protected $table = 'access_rights';

    protected $fillable = [
        'organization_id',
        'role_id',
        'module_id',
        'sub_module_id',
        'can_create',
        'can_read',
        'can_update',
        'can_delete',
        'created_by',
        'updated_by',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

    public function role()
    {
        return $this->belongsTo(role::class);
    }

    public function module()
    {
        return $this->belongsTo(module::class);
    }

    public function subModule()
    {
        return $this->belongsTo(sub_module::class, 'sub_module_id');
    }
}
