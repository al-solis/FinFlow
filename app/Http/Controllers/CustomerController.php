<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Services\AuthorizationService;
use App\Services\PiiMaskingService;
use App\Services\SystemSettings;
use App\Traits\MasksPii;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\chart_of_account;
use App\Models\currency;
use App\Models\customer;
use App\Models\customer_category;
use App\Models\main_account;
use App\Models\payment_method;
use App\Models\tax_group;
use App\Models\tax_master;
use App\Models\term;
use App\Models\ar_setting;

class CustomerController extends Controller
{
    use MasksPii;

    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    /**
     * PiiMaskingService only computes its mask level inside setContext(); without it the
     * level stays FULL, so every screen must set the context before masking or checking access.
     */
    protected function masker(): PiiMaskingService
    {
        return $this->getPiiMasker()->setContext(Modules::AR, Modules::AR_CUST);
    }

    /**
     * Create/edit screens show unmasked values, and a masked value must never be posted back
     * and saved over real data. Only users the masker treats as full-access may edit.
     */
    protected function requireUnmasked(): void
    {
        abort_unless($this->masker()->hasFullAccess(), 403, 'You do not have access to the unmasked customer data required to edit.');
    }

    public function index(Request $request)
    {
        $authz = $this->authz();
        $authz->authorizeRead(Modules::AR, Modules::AR_CUST);

        $masker = $this->masker();
        $orgId = SystemSettings::getOrganizationId();

        $customers = customer::with(['category', 'paymentTerm'])
            ->where('organization_id', $orgId)
            ->withSum(['invoices as open_balance' => fn($q) => $q->open()], 'amount_due')
            ->when($request->filled('searchname'), function ($q) use ($request, $masker) {
                $s = '%' . $request->searchname . '%';
                $q->where(function ($w) use ($s, $masker) {
                    $w->where('code', 'like', $s)->orWhere('name', 'like', $s);
                    // Searching by TIN would let a masked user confirm a full TIN by guessing
                    if ($masker->hasFullAccess()) {
                        $w->orWhere('tax_id', 'like', $s);
                    }
                });
            })
            ->when($request->filled('searchcategory'), fn($q) => $q->where('customer_category_id', $request->searchcategory))
            ->when($request->filled('searchstatus'), fn($q) => $q->where('is_active', $request->searchstatus === '1'))
            ->orderBy('name')
            ->paginate(config('app.paginate'))
            ->withQueryString();

        $customers->setCollection($this->maskCollectionPii($customers->getCollection(), customer::PII_FIELDS));

        return view('ar.customer.index', [
            'customers' => $customers,
            'categories' => customer_category::where('organization_id', $orgId)->where('status', 1)->orderBy('name')->get(),
            'canCreate' => $authz->canCreate(Modules::AR, Modules::AR_CUST),
            'canUpdate' => $authz->canUpdate(Modules::AR, Modules::AR_CUST) && $masker->hasFullAccess(),
            'maskBadge' => $masker->getMaskLevelBadge(),
        ]);
    }

    public function create()
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_CUST);
        $this->requireUnmasked();

        $orgId = SystemSettings::getOrganizationId();

        $taxMaster = tax_master::where('organization_id', SystemSettings::getOrganizationId())
            ->where('status', 1)
            ->whereHas('taxType', fn($q) => $q->where('code', 'CWT'))
            ->orderBy('rate')
            ->get();

        return view('ar.customer.form', $this->formData(new customer([
            'is_active' => true,
            'vat_classification' => 'vatable',
            'country' => 'Philippines',
        ])));
    }

    public function store(Request $request)
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_CUST);
        $this->requireUnmasked();

        $data = $this->normalize($request, $request->validate($this->rules()));
        $data['organization_id'] = SystemSettings::getOrganizationId();
        $data['created_by'] = $data['updated_by'] = Auth::id();

        $customer = customer::create($data);

        return redirect()->route('ar.cust')->with('success', "Customer {$customer->code} created.");
    }

    public function edit(customer $customer)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_CUST);
        $this->requireUnmasked();
        $this->assertOwnOrganization($customer);

        return view('ar.customer.form', $this->formData($customer));
    }

    public function update(Request $request, customer $customer)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_CUST);
        $this->requireUnmasked();
        $this->assertOwnOrganization($customer);

        $data = $this->normalize($request, $request->validate($this->rules($customer)));
        $data['updated_by'] = Auth::id();

        $customer->update($data);

        return redirect()->route('ar.cust')->with('success', "Customer {$customer->code} updated.");
    }

    // ------------------------------------------------------------------

    protected function assertOwnOrganization(customer $customer): void
    {
        abort_if((int) $customer->organization_id !== (int) SystemSettings::getOrganizationId(), 404);
    }

    protected function normalize(Request $request, array $data): array
    {
        foreach (['is_withholding_agent', 'credit_hold', 'is_active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        $data['credit_limit'] = $data['credit_limit'] ?? 0;

        return $data;
    }

    protected function rules(?customer $customer = null): array
    {
        $orgId = SystemSettings::getOrganizationId();

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'code')->where('organization_id', $orgId)->ignore($customer?->id)
            ],
            'name' => 'required|string|max:150',
            'legal_name' => 'nullable|string|max:150',
            'customer_category_id' => 'required|exists:customer_categories,id',
            'industry' => 'nullable|string|max:100',

            // PH TIN: 000-000-000 or 000-000-000-000 (dashes optional)
            'tax_id' => ['nullable', 'string', 'max:30', 'regex:/^\d{3}-?\d{3}-?\d{3}(-?\d{3,5})?$/'],
            'tax_branch_code' => 'nullable|string|max:10',
            'registration_no' => 'nullable|string|max:100',
            'vat_classification' => ['required', Rule::in(['vatable', 'zero_rated', 'exempt', 'non_vat'])],
            'default_cwt_tax_id' => 'nullable|exists:tax_masters,id',
            'tax_group_id' => 'nullable|exists:tax_groups,id',

            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',

            'contact_person' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:100',
            'contact_notes' => 'nullable|string',

            'currency_id' => 'nullable|exists:currencies,id',
            'payment_term_id' => 'nullable|exists:terms,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'ar_account_id' => 'nullable|exists:main_accounts,id',
            'default_ar_chart_of_account_id' => 'nullable|exists:chart_of_accounts,id',
            'default_sales_discount_account_id' => 'nullable|exists:chart_of_accounts,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ];
    }

    protected function formData(customer $customer): array
    {
        $orgId = SystemSettings::getOrganizationId();
        $receivables = fn($q) => $q->whereIn('description', ['Accounts Receivable', 'Receivables']);

        return [
            'customer' => $customer,
            'defaultARSettings' => ar_setting::forCurrentOrg(),
            'categories' => customer_category::where('organization_id', $orgId)->where('status', 1)->orderBy('name')->get(),
            'currencies' => currency::where('status', 1)->orderBy('code')->get(),
            'terms' => term::where('organization_id', $orgId)->where('status', 1)->orderBy('days')->get(),
            'paymentMethods' => payment_method::where('organization_id', $orgId)->where('status', 1)->orderBy('name')->get(),
            'taxGroups' => tax_group::where('organization_id', $orgId)->where('status', 1)->orderBy('name')->get(),
            'cwtTaxes' => tax_master::where('organization_id', $orgId)->where('status', 1)
                ->whereHas('taxType', fn($q) => $q->where('code', 'CWT'))->orderBy('rate')->get(),
            'mainAccounts' => main_account::where('organization_id', $orgId)->where('status', 1)
                ->whereHas('accountSubcategory', $receivables)->orderBy('code')->get(),
            'arAccounts' => chart_of_account::where('organization_id', $orgId)
                ->where('is_posting', true)->where('status', true)
                ->whereHas('accountSubcategory', $receivables)->orderBy('account_code')->get(),
            'discountAccounts' => chart_of_account::where('organization_id', $orgId)
                ->where('is_posting', true)->where('status', true)
                ->whereHas('accountType', fn($q) => $q->where('code', 'REVENUE'))
                ->orderBy('account_code')
                ->get(['id', 'account_code', 'account_name']),
        ];
    }
}
