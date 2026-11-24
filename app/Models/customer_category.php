<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class customer_category extends Model
{
    protected $table = 'customer_categories';

    protected $fillable = [
        'organization_id', 'code', 'name', 'description', 'status', 'created_by', 'updated_by',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

    public function customers()
    {
        return $this->hasMany(customer::class, 'customer_category_id');
    }
}
