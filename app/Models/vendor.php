<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\organization;
use App\Models\payment_method;
use App\Models\term;
use App\Models\currency;
use App\Models\VendorCategory;
use App\Models\tax_group;
use App\Models\main_account;
use App\Models\chart_of_account;
use App\Models\vendor_bank_account;
use App\Models\vendor_attachment;
use App\Models\ap_invoice;
use App\Models\ap_invoice_line;
use App\Models\ap_payment;
use App\Models\account_structure;

class vendor extends Model
{
    protected $table = 'vendors';

    protected $fillable = [
        'organization_id',
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
        'default_ap_chart_of_account_id',
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

    public const PII_FIELDS = [
        'name' => 'full_name',
        'legal_name' => 'full_name',
        'contact_person' => 'full_name',
        'email' => 'email',
        'phone' => 'phone',
        'mobile' => 'mobile',
        'tax_id' => 'tax_id',
        'address1' => 'address',
        'address2' => 'address',
        'contact_notes' => 'address',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

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

    public function defaultApChartOfAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'default_ap_chart_of_account_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getInvoiceTotalAmountAttribute()
    {
        $accountStructure = account_structure::where('organization_id', $this->organization_id)
            ->where('is_default', true)
            ->first();

        if (!$accountStructure) {
            return 0;
        }

        return ap_invoice::where('organization_id', $this->organization_id)
            ->where('vendor_id', $this->id)
            ->whereDate('invoice_date', '>=', $accountStructure->start_date)
            ->whereDate('invoice_date', '<=', $accountStructure->end_date)
            ->sum('net_amount');
    }

    public function getInvoiceBalanceAttribute()
    {
        $accountStructure = account_structure::where('organization_id', $this->organization_id)
            ->where('is_default', true)
            ->first();

        if (!$accountStructure) {
            return 0;
        }

        return ap_invoice::where('organization_id', $this->organization_id)
            ->where('vendor_id', $this->id)
            ->whereDate('invoice_date', '>=', $accountStructure->start_date)
            ->whereDate('invoice_date', '<=', $accountStructure->end_date)
            ->sum('amount_due');
    }

}
