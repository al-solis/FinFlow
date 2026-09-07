<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettings;
use App\Constants\Modules;
use App\Traits\AuthorizesAccessRights;
use App\Models\VendorCategory;

class VendorCategoryController extends Controller
{
    use AuthorizesAccessRights;

    public function index(Request $request)
    {
        $this->authorizeRead(Modules::AP, Modules::AP_CATEGORIES);

        $settings = SystemSettings::get();

        $vendorCategories = VendorCategory::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('searchstatus'), function ($q) use ($request) {
                $q->where('status', $request->searchstatus);
            })
            ->where('organization_id', $settings->id)
            ->orderBy('name')
            ->paginate(config('app.paginate', 15))
            ->withQueryString();

        // Stats
        $totalCategories = VendorCategory::where('organization_id', $settings->id)->count();
        $activeCategories = VendorCategory::where('organization_id', $settings->id)->where('status', 1)->count();
        $inactiveCategories = VendorCategory::where('organization_id', $settings->id)->where('status', 0)->count();

        return view('ap.vendor.category.index', compact(
            'vendorCategories',
            'totalCategories',
            'activeCategories',
            'inactiveCategories'
        ));
    }

    public function create()
    {
        $this->authorizeCreate(Modules::AP, Modules::AP_CATEGORIES);

        return view('ap.vendor.category.form', [
            'category' => new VendorCategory(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeCreate(Modules::AP, Modules::AP_CATEGORIES);

        $settings = SystemSettings::get();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vendor_categories', 'code')->where(function ($query) use ($settings) {
                    return $query->where('organization_id', $settings->id);
                })
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('vendor_categories', 'name')->where(function ($query) use ($settings) {
                    return $query->where('organization_id', $settings->id);
                })
            ],
            'description' => 'nullable|string|max:255',
            'status' => 'required|boolean',
        ]);

        VendorCategory::create([
            'organization_id' => $settings->id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('ap.categories')->with('success', 'Vendor Category created successfully.');
    }

    public function edit(VendorCategory $vendorCategory)
    {
        $this->authorizeUpdate(Modules::AP, Modules::AP_CATEGORIES);

        return view('ap.vendor.category.form', [
            'category' => $vendorCategory,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, VendorCategory $vendorCategory)
    {
        $this->authorizeUpdate(Modules::AP, Modules::AP_CATEGORIES);

        $settings = SystemSettings::get();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vendor_categories', 'code')->where(function ($query) use ($settings) {
                    return $query->where('organization_id', $settings->id);
                })->ignore($vendorCategory->id)
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('vendor_categories', 'name')->where(function ($query) use ($settings) {
                    return $query->where('organization_id', $settings->id);
                })->ignore($vendorCategory->id)
            ],
            'description' => 'nullable|string|max:255',
            'status' => 'required|boolean',
        ]);

        $vendorCategory->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('ap.categories')->with('success', 'Vendor Category updated successfully.');
    }

    public function destroy(VendorCategory $vendorCategory)
    {
        $this->authorizeDelete(Modules::AP, Modules::AP_CATEGORIES);

        // Check if category is in use
        $inUse = \App\Models\vendor::where('vendor_category_id', $vendorCategory->id)->exists();

        if ($inUse) {
            return back()->with('error', 'Cannot delete this category as it is currently in use by one or more vendors.');
        }

        $vendorCategory->delete();

        return redirect()->route('ap.categories')->with('success', 'Vendor Category deleted successfully.');
    }
}