<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class vendor extends Model
{
    protected $table = 'vendors';

    protected $fillable = [
        'code',
        'name',
        'legal_name',
        'vendor_category_id',
        'industry',
        'tax_id',
        'tax_branch_code',
        'registration_no',
        'bir_rdo_code',
        'tax_group_id',
        'address1',
        'address2',
        'city',
        'province',
        'country',
        'zip_code',
        'contact_person',
        'position',
        'email',
        'phone',
        'mobile',
        'website',
        'contact_notes',
        'currency_id',
        'payment_term_id',
        'payment_method_id',
        'ap_account_id',
        'credit_limit',
        'requires_po',
        'lead_time',
        'preferred_vendor',
        'is_active',
        'is_blacklisted',
        'blacklist_reason',
        'remarks',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(VendorCategory::class, 'vendor_category_id');
    }

    public function currency()
    {
        return $this->belongsTo(currency::class, 'currency_id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(term::class, 'payment_term_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(payment_method::class, 'payment_method_id');
    }

    public function taxGroup()
    {
        return $this->belongsTo(tax_group::class, 'tax_group_id');
    }

    public function apAccount()
    {
        return $this->belongsTo(main_account::class, 'ap_account_id');
    }

    public function bankAccounts()
    {
        return $this->hasMany(vendor_bank_account::class, 'vendor_id');
    }

    public function attachments()
    {
        return $this->hasMany(vendor_attachment::class, 'vendor_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
