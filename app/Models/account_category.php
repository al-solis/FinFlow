<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
use App\Models\account_type;

class account_category extends Model
{
    protected $table = 'account_categories';

    protected $fillable = [
        'organization_id',
        'account_type_id',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

    public function accountType()
    {
        return $this->belongsTo(account_type::class, 'account_type_id');
    }
}
