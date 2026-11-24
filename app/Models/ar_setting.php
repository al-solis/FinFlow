<?php

namespace App\Models;

use App\Services\SystemSettings;
use Illuminate\Database\Eloquent\Model;
use App\Models\Organization;
use App\Models\chart_of_account;
use App\Models\term;
use App\Models\customer_category;

class ar_setting extends Model
{
    protected $table = 'ar_settings';
    protected $guarded = [];

    protected $casts = [
        'default_ar_account_id' => 'integer',
        'default_revenue_account_id' => 'integer',
        'default_sales_discount_account_id' => 'integer',
        'record_sales_discount_in_gl' => 'boolean',
        'default_sales_return_account_id' => 'integer',
        'default_payment_term_id' => 'integer',
        'default_customer_category_id' => 'integer',
    ];

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function defaultArAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'default_ar_account_id');
    }

    public function defaultRevenueAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'default_revenue_account_id');
    }

    public function defaultSalesDiscountAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'default_sales_discount_account_id');
    }

    public function defaultSalesReturnAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'default_sales_return_account_id');
    }

    public function defaultPaymentTerm()
    {
        return $this->belongsTo(term::class, 'default_payment_term_id');
    }

    public function defaultCustomerCategory()
    {
        return $this->belongsTo(customer_category::class, 'default_customer_category_id');
    }

    // ------------------------------------------------------------------
    // Accessors
    // ------------------------------------------------------------------

    /**
     * Return the current organization's settings, creating an unsaved
     * instance if none exists yet. Never writes to the DB.
     */
    public static function forCurrentOrg(): self
    {
        $orgId = SystemSettings::getOrganizationId();

        return static::firstOrNew(['organization_id' => $orgId]);
    }

    /**
     * Resolve an account id using the standard fallback chain:
     *   1. The explicit value provided by the caller (customer / item / etc.)
     *   2. The organization's AR setting for this slot
     *   3. null — the caller decides whether null is fatal
     *
     * Keeping this in one place means the invoice form, the posting
     * service, and any future collection code all resolve accounts
     * in the same order.
     */
    public static function resolveAccountId(?int $explicit, string $settingColumn, int $orgId): ?int
    {
        if ($explicit) {
            return $explicit;
        }

        $value = static::where('organization_id', $orgId)->value($settingColumn);

        return $value ? (int) $value : null;
    }
}