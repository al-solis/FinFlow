<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\account_subcategory;
use App\Models\account_category;

class AccountSubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');
        $searchcategory = $request->input('searchcategory');

        $accountCategories = account_category::all();
        $accountSubcategories = account_subcategory::all();
        $totalSubCategories = account_subcategory::count();
        $activeSubCategories = account_subcategory::where('status', '1')->count();
        $inactiveSubCategories = account_subcategory::where('status', '0')->count();

        $query = account_subcategory::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
            });
        }

        if ($searchcategory) {
            $query->where('account_category_id', $searchcategory);
        }

        if ($searchstatus != null) {
            $query->where('status', $searchstatus);
        }

        $subcategories = $query->paginate(env('APP_PAGINATE_PER_PAGE', 10))
            ->appends([
                'search' => $search,
                'searchcategory' => $searchcategory,
                'searchstatus' => $searchstatus,
            ]);

        return view('gl.chart.subcategory.index', compact('totalSubCategories', 'activeSubCategories', 'inactiveSubCategories', 'subcategories', 'accountSubcategories', 'accountCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required',
            'category' => 'required',
            'status' => 'required|integer'
        ]);

        account_subcategory::create([
            'account_category_id' => $request->category,
            'description' => $request->description,
            'status' => $request->status,
            'created_by' => Auth::user()->id,
            'created_at' => now()
        ]);

        return redirect()->route('gl.chart.subcategory.index')->with('success', 'Account sub-category successfully created.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'edit_description' => 'required',
            'edit_category' => 'required',
            'edit_status' => 'required|integer'
        ]);

        $subcategory = account_subcategory::findOrFail($id);
        $subcategory->update([
            'account_category_id' => $request->edit_category,
            'description' => $request->edit_description,
            'status' => $request->edit_status,
            'updated_by' => Auth::user()->id,
            'updated_at' => now()
        ]);

        return redirect()->route('gl.chart.subcategory.index')->with('success', 'Account sub-category successfully updated.');
    }
}
