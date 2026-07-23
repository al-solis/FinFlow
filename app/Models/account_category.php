<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class account_category extends Model
{
    protected $table = 'account_categories';

    protected $fillable = [
        'account_type_id',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function accountType()
    {
        return $this->belongsTo(account_type::class, 'account_type_id');
    }
}
