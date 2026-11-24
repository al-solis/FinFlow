<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Models\ar_setting;
use App\Models\chart_of_account;
use App\Models\customer_category;
use App\Models\term;
use App\Services\AuthorizationService;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArSettingController extends Controller
{
    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    public function edit()
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_SETTINGS);

        $orgId = SystemSettings::getOrganizationId();
        $settings = ar_setting::firstOrNew(['organization_id' => $orgId]);

        return view('ar.settings.edit', [
            'settings' => $settings,
            'arAccounts' => $this->chartAccounts($orgId, ['ASSET'], ['Accounts Receivable', 'Receivables']),
            'revenueAccounts' => $this->chartAccounts($orgId, ['REVENUE'], []),
            'discountAccounts' => $this->chartAccounts($orgId, ['REVENUE'], []),
            'returnAccounts' => $this->chartAccounts($orgId, ['REVENUE'], []),
            'terms' => term::where('organization_id', $orgId)
                ->where('status', 1)
                ->orderBy('days')
                ->get(['id', 'name', 'days']),
            'customerCategories' => customer_category::where('organization_id', $orgId)
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_SETTINGS);

        $orgId = SystemSettings::getOrganizationId();

        $data = $request->validate([
            'default_ar_account_id' => 'nullable|exists:chart_of_accounts,id',
            'default_revenue_account_id' => 'nullable|exists:chart_of_accounts,id',
            'default_sales_discount_account_id' => 'nullable|exists:chart_of_accounts,id',
            'record_sales_discount_in_gl' => 'boolean',
            'default_sales_return_account_id' => 'nullable|exists:chart_of_accounts,id',
            'default_payment_term_id' => 'nullable|exists:terms,id',
            'default_customer_category_id' => 'nullable|exists:customer_categories,id',
        ]);

        $data['organization_id'] = $orgId;
        $data['record_sales_discount_in_gl'] = $request->boolean('record_sales_discount_in_gl');
        $data['updated_by'] = Auth::id();

        ar_setting::updateOrCreate(
            ['organization_id' => $orgId],
            $data
        );

        return redirect()->route('ar.settings')->with('success', 'AR settings updated.');
    }

    protected function chartAccounts(int $orgId, array $typeCodes, array $subcategories = [])
    {
        return chart_of_account::where('organization_id', $orgId)
            ->where('is_posting', true)
            ->where('status', true)
            ->whereHas('accountType', fn($q) => $q->whereIn('code', $typeCodes))
            ->when($subcategories, fn($q) => $q->whereHas(
                'accountSubcategory',
                fn($s) => $s->whereIn('description', $subcategories)
            ))
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);
    }
}