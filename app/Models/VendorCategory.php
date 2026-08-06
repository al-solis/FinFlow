<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
class VendorCategory extends Model
{
    protected $table = 'vendor_categories';

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }
}
