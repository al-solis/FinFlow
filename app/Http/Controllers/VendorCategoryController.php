<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorCategory;
use Illuminate\Support\Facades\Auth;

class VendorCategoryController extends Controller
{
    public function index(Request $request)
    {
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

        $vendorCategories = $query->paginate(config('app.paginate'))
            ->appends(['search' => $search, 'searchstatus' => $searchstatus]);

        return view('ap.vendor.category.index', compact('vendorCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|max:20|unique:vendor_categories,code',
            'name' => 'required|max:100',
            'description' => 'nullable',
            'status' => 'required|boolean',
        ]);

        VendorCategory::create([
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
        $request->validate([
            'edit_code' => 'required|max:20|unique:vendor_categories,code,' . $vendorCategory->id,
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
