<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
use App\Models\chart_of_account;
use App\Models\tax_master;

class item extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'unit_of_measure',
        'gl_account_id',
        'default_tax_id',
        'default_unit_price',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'default_unit_price' => 'decimal:2',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

    public function glAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

    public function defaultTax()
    {
        return $this->belongsTo(tax_master::class, 'default_tax_id');
    }

    public function rfdDetails()
    {
        return $this->hasMany(rfd_detail::class, 'item_id');
    }
}