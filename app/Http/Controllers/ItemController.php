<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Models\chart_of_account;
use App\Models\item;
use App\Models\tax_master;
use App\Services\AuthorizationService;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\unit_of_measure;
use App\Models\ar_setting;

class ItemController extends Controller
{
    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    /** items.organization_id is nullable, so treat NULL as "shared with this organization". */
    protected function inOrg($query)
    {
        $orgId = SystemSettings::getOrganizationId();

        return $query->where(fn($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'));
    }

    public function index(Request $request)
    {
        $authz = $this->authz();
        $authz->authorizeRead(Modules::MASTER, Modules::MASTER_ITEM);

        $items = $this->inOrg(item::with(['glAccount', 'salesGlAccount']))
            ->when($request->filled('searchname'), function ($q) use ($request) {
                $s = '%' . $request->searchname . '%';
                $q->where(fn($w) => $w->where('code', 'like', $s)->orWhere('name', 'like', $s)->orWhere('description', 'like', $s));
            })
            ->when($request->filled('searchstatus'), fn($q) => $q->where('status', (int) $request->searchstatus))
            ->when($request->filled('searchsellable'), fn($q) => $q->where('is_sellable', $request->searchsellable === '1'))
            ->orderBy('code')
            ->paginate(config('app.paginate'))
            ->withQueryString();

        return view('master.item.index', [
            'items' => $items,
            'totalCount' => $this->inOrg(item::query())->count(),
            'activeCount' => $this->inOrg(item::query())->where('status', 1)->count(),
            'sellableCount' => $this->inOrg(item::query())->where('is_sellable', true)->count(),
            'canRead' => $authz->canRead(Modules::MASTER, Modules::MASTER_ITEM),
            'canCreate' => $authz->canCreate(Modules::MASTER, Modules::MASTER_ITEM),
            'canUpdate' => $authz->canUpdate(Modules::MASTER, Modules::MASTER_ITEM),
            'canDelete' => $authz->canDelete(Modules::MASTER, Modules::MASTER_ITEM),
        ]);
    }

    public function create()
    {
        $this->authz()->authorizeCreate(Modules::MASTER, Modules::MASTER_ITEM);

        return view('master.item.form', $this->formData(new item(['status' => 1, 'is_sellable' => false])));
    }

    public function store(Request $request)
    {
        $this->authz()->authorizeCreate(Modules::MASTER, Modules::MASTER_ITEM);

        $data = $this->normalize($request, $request->validate($this->rules($request)));
        $data['organization_id'] = SystemSettings::getOrganizationId();
        $data['created_by'] = $data['updated_by'] = Auth::id();

        $item = item::create($data);

        return redirect()->route('master.item')->with('success', "Item {$item->code} created.");
    }

    public function edit(item $item)
    {
        $this->authz()->authorizeUpdate(Modules::MASTER, Modules::MASTER_ITEM);

        return view('master.item.form', $this->formData($item));
    }

    public function update(Request $request, item $item)
    {
        $this->authz()->authorizeUpdate(Modules::MASTER, Modules::MASTER_ITEM);

        $data = $this->normalize($request, $request->validate($this->rules($request, $item)));
        $data['updated_by'] = Auth::id();

        $item->update($data);

        return redirect()->route('master.item')->with('success', "Item {$item->code} updated.");
    }

    public function destroy(item $item)
    {
        $this->authz()->authorizeDelete(Modules::MASTER, Modules::MASTER_ITEM);

        $uses = $item->rfdDetails()->count() + $item->salesInvoiceLines()->count();
        if ($uses > 0) {
            return redirect()->route('master.item')
                ->with('error', "Item {$item->code} is used on {$uses} transaction line(s). Set it to Inactive instead of deleting.");
        }

        $code = $item->code;
        $item->delete();

        return redirect()->route('master.item')->with('success', "Item {$code} deleted.");
    }

    public function lookup(Request $request)
    {
        $sales = $request->query('context') === 'sales';

        $items = $this->inOrg(item::query())
            ->where('status', 1)
            ->when($sales, fn($q) => $q->where('is_sellable', true))
            ->when($request->filled('q'), function ($q) use ($request) {
                $s = '%' . $request->query('q') . '%';
                $q->where(fn($w) => $w->where('code', 'like', $s)->orWhere('name', 'like', $s));
            })
            ->orderBy('code')
            ->limit(20)
            ->get();

        return response()->json($items->map(fn($i) => [
            'id' => $i->id,
            'code' => $i->code,
            'name' => $i->name,
            'description' => $i->description ?: $i->name,
            'uoms' => $i->unit_of_measure,
            'gl_account_id' => $sales ? $i->sales_gl_account_id : $i->gl_account_id,
            'tax_id' => $sales ? $i->default_sales_tax_id : $i->default_tax_id,
            'unit_price' => (float) ($sales ? $i->default_sales_price : $i->default_unit_price),
        ]));
    }

    // ------------------------------------------------------------------

    protected function normalize(Request $request, array $data): array
    {
        $data['is_sellable'] = $request->boolean('is_sellable');

        // Don't leave stale selling defaults on an item that is no longer sellable
        if (!$data['is_sellable']) {
            $data['sales_gl_account_id'] = $data['default_sales_tax_id'] = $data['default_sales_price'] = $data['sales_uom_id'] = null;
        }

        return $data;
    }

    protected function rules(Request $request, ?item $item = null): array
    {
        return [
            // items.code has a global unique index in your migration
            'code' => ['required', 'string', 'max:255', Rule::unique('items', 'code')->ignore($item?->id)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'uom_id' => 'required|exists:unit_of_measures,id',
            'purchase_uom_id' => 'nullable|exists:unit_of_measures,id',
            'sales_uom_id' => 'nullable|exists:unit_of_measures,id',
            'gl_account_id' => 'nullable|exists:chart_of_accounts,id',
            'default_tax_id' => 'nullable|exists:tax_masters,id',
            'default_unit_price' => 'nullable|numeric|min:0',
            'sales_gl_account_id' => [Rule::requiredIf($request->boolean('is_sellable')), 'nullable', 'exists:chart_of_accounts,id'],
            'default_sales_tax_id' => 'nullable|exists:tax_masters,id',
            'default_sales_price' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in([0, 1])],
        ];
    }

    protected function formData(item $item): array
    {
        $orgId = SystemSettings::getOrganizationId();
        $posting = fn($q) => $q->where('organization_id', $orgId)->where('is_posting', true)->where('status', true);

        return [
            'item' => $item,
            'uoms' => unit_of_measure::where('organization_id', $orgId)
                ->where('status', true)
                ->orderBy('code')->get(),
            'purchaseAccounts' => chart_of_account::where($posting)
                ->whereHas('accountType', fn($q) => $q->whereIn('code', ['EXPENSE', 'ASSET']))
                ->orderBy('account_code')->get(),
            'revenueAccounts' => chart_of_account::where($posting)
                ->whereHas('accountType', fn($q) => $q->where('code', 'REVENUE'))
                ->orderBy('account_code')->get(),
            // Purchase defaults: any active tax except creditable withholding (that belongs on collections)
            'purchaseTaxes' => tax_master::where('organization_id', $orgId)->where('status', 1)
                ->whereHas('taxType', fn($q) => $q->where('code', '!=', 'CWT'))->orderBy('code')->get(),
            // Sales defaults: VAT / exempt only (withholding is never put on an invoice line)
            'salesTaxes' => tax_master::where('organization_id', $orgId)->where('status', 1)
                ->whereHas('taxType', fn($q) => $q->whereIn('code', ['VAT', 'EXEMPT', 'SALES_CLASS']))
                ->where(fn($q) => $q->where('direction', 'output')
                    ->orWhere('direction', 'both')
                    ->orWhereNull('direction'))
                ->orderBy('code')->get(),
            'arSettings' => ar_setting::forCurrentOrg(),
        ];
    }
}