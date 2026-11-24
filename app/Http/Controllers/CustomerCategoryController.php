<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Models\customer_category;
use App\Services\AuthorizationService;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerCategoryController extends Controller
{
    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    public function index(Request $request)
    {
        $authz = $this->authz();
        $authz->authorizeRead(Modules::AR, Modules::AR_CATG);

        $orgId = SystemSettings::getOrganizationId();
        $base = customer_category::where('organization_id', $orgId);

        $categories = (clone $base)
            ->withCount('customers')
            ->when($request->filled('searchname'), function ($q) use ($request) {
                $s = '%' . $request->searchname . '%';
                $q->where(fn($w) => $w->where('code', 'like', $s)
                    ->orWhere('name', 'like', $s)
                    ->orWhere('description', 'like', $s));
            })
            ->when($request->filled('searchstatus'), fn($q) => $q->where('status', (int) $request->searchstatus))
            ->orderBy('code')
            ->paginate(config('app.paginate'))
            ->withQueryString();

        return view('ar.category.index', [
            'categories' => $categories,
            'totalCount' => (clone $base)->count(),
            'activeCount' => (clone $base)->where('status', 1)->count(),
            'inactiveCount' => (clone $base)->where('status', 0)->count(),
            'canCreate' => $authz->canCreate(Modules::AR, Modules::AR_CATG),
            'canUpdate' => $authz->canUpdate(Modules::AR, Modules::AR_CATG),
            'canDelete' => $authz->canDelete(Modules::AR, Modules::AR_CATG),
        ]);
    }

    public function create()
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_CATG);

        return view('ar.category.form', ['category' => new customer_category(['status' => 1])]);
    }

    public function store(Request $request)
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_CATG);

        $data = $request->validate($this->rules());
        $data['organization_id'] = SystemSettings::getOrganizationId();
        $data['created_by'] = $data['updated_by'] = Auth::id();

        $category = customer_category::create($data);

        return redirect()->route('ar.catg')->with('success', "Category {$category->code} created.");
    }

    public function edit(customer_category $category)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_CATG);
        $this->assertOwnOrganization($category);

        return view('ar.category.form', ['category' => $category]);
    }

    public function update(Request $request, customer_category $category)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_CATG);
        $this->assertOwnOrganization($category);

        $data = $request->validate($this->rules($category));
        $data['updated_by'] = Auth::id();

        $category->update($data);

        return redirect()->route('ar.catg')->with('success', "Category {$category->code} updated.");
    }

    public function destroy(customer_category $category)
    {
        $this->authz()->authorizeDelete(Modules::AR, Modules::AR_CATG);
        $this->assertOwnOrganization($category);

        $inUse = $category->customers()->count();
        if ($inUse > 0) {
            return redirect()->route('ar.catg')
                ->with('error', "Category {$category->code} is used by {$inUse} customer(s). Deactivate it instead of deleting.");
        }

        $code = $category->code;
        $category->delete();

        return redirect()->route('ar.catg')->with('success', "Category {$code} deleted.");
    }

    protected function assertOwnOrganization(customer_category $category): void
    {
        abort_if((int) $category->organization_id !== (int) SystemSettings::getOrganizationId(), 404);
    }

    protected function rules(?customer_category $category = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customer_categories', 'code')
                    ->where('organization_id', SystemSettings::getOrganizationId())
                    ->ignore($category?->id)
            ],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'status' => ['required', Rule::in([0, 1])],
        ];
    }
}
