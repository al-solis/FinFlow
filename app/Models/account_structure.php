<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class account_structure extends Model
{
    protected $table = 'account_structures';

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_default',
        'status',
        'last_synced_at',
        'last_synced_by',
        'created_by',
        'updated_by',
    ];

    public function details()
    {
        return $this->hasMany(account_structure_detail::class, 'account_structure_id');
    }

    public function lastSyncedBy()
    {
        return $this->belongsTo(User::class, 'last_synced_by');
    }
}
