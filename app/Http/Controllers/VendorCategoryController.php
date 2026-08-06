<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorCategory;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettings;
use Illuminate\Validation\Rule;

class VendorCategoryController extends Controller
{
    public function index(Request $request)
    {
        $settings = SystemSettings::get();
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');

        $query = VendorCategory::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        if ($searchstatus !== null) {
            $query->where('status', $searchstatus);
        }

        $query->where('organization_id', $settings->id);

        $vendorCategories = $query->paginate(config('app.paginate'))
            ->appends(['search' => $search, 'searchstatus' => $searchstatus]);

        return view('ap.vendor.category.index', compact('vendorCategories'));
    }

    public function store(Request $request)
    {
        $settings = SystemSettings::get();
        $request->validate([
            'code' => [
                'required',
                'max:20',
                Rule::unique('vendor_categories', 'code')->where(function ($query) use ($settings) {
                    return $query->where('organization_id', $settings->id);
                })
            ],
            'name' => 'required|max:100',
            'description' => 'nullable',
            'status' => 'required|boolean',
        ]);

        VendorCategory::create([
            'organization_id' => $settings->id,
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('ap.categories')->with('success', 'Vendor Category created successfully.');
    }

    public function update(Request $request, VendorCategory $vendorCategory)
    {
        $settings = SystemSettings::get();
        $request->validate([
            'edit_code' => [
                'required',
                'max:20',
                Rule::unique('vendor_categories', 'code')->where(function ($query) use ($settings) {
                    return $query->where('organization_id', $settings->id);
                })->ignore($vendorCategory->id)
            ],
            'edit_name' => 'required|max:100',
            'edit_description' => 'nullable',
            'edit_status' => 'required|boolean',
        ]);

        $vendorCategory->update([
            'code' => $request->edit_code,
            'name' => $request->edit_name,
            'description' => $request->edit_description,
            'status' => $request->edit_status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('ap.categories')->with('success', 'Vendor Category updated successfully.');
    }
}
