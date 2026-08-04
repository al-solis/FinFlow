<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'organization_code',
        'name',
        'short_name',
        'legal_name',

        'industry',
        'business_type',

        'tax_id',
        'registration_no',
        'tax_branch_code',
        'bir_rdo_code',

        'description',

        'address',
        'city',
        'province',
        'country',
        'zip_code',

        'contact_person',
        'phone',
        'mobile',
        'email',
        'website',

        'currency_id',
        'timezone',
        'language',
        'date_format',
        'number_format',
        'decimal_places',
        'accounting_method',

        'logo',

        'status',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function currency()
    {
        return $this->belongsTo(currency::class, 'currency_id');
    }
}