<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'legal_name',
        'customer_category_id',
        'industry',
        'tax_id',
        'tax_branch_code',
        'registration_no',
        'vat_classification',
        'is_withholding_agent',
        'default_cwt_tax_id',
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
        'ar_account_id',
        'default_ar_chart_of_account_id',
        'default_sales_discount_account_id',
        'credit_limit',
        'credit_hold',
        'is_active',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_withholding_agent' => 'boolean',
        'credit_hold' => 'boolean',
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    // Same masking convention as vendor (Data Privacy Act section of the manual)
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
        return $this->belongsTo(customer_category::class, 'customer_category_id');
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

    public function defaultCwtTax()
    {
        return $this->belongsTo(tax_master::class, 'default_cwt_tax_id');
    }

    public function arAccount()
    {
        return $this->belongsTo(main_account::class, 'ar_account_id');
    }

    public function defaultArChartOfAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'default_ar_chart_of_account_id');
    }

    public function defaultSalesDiscountAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'default_sales_discount_account_id');
    }

    public function invoices()
    {
        return $this->hasMany(sales_invoice::class, 'customer_id');
    }

    public function collections()
    {
        return $this->hasMany(collection::class, 'customer_id');
    }

    /** Total unpaid balance across all open invoices (used for credit control). */
    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->invoices()->open()->sum('amount_due');
    }

    public function exceedsCreditLimit(float $additional = 0): bool
    {
        $limit = (float) $this->credit_limit;

        return $limit > 0 && ($this->outstanding_balance + $additional) > $limit;
    }
}
