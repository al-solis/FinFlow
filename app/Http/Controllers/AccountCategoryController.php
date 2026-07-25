<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\account_category;
use App\Models\main_account;
use App\Models\account_type;

class AccountCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');
        $searchtype = $request->input('searchtype');

        $accountTypes = account_type::all();
        $accountCategories = account_category::all();
        $totalCategories = account_category::count();
        $activeCategories = account_category::where('status', '1')->count();
        $inactiveCategories = account_category::where('status', '0')->count();

        $query = account_category::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
            });
        }

        if ($searchtype) {
            $query->where('account_type_id', $searchtype);
        }

        if ($searchstatus != null) {
            $query->where('status', $searchstatus);
        }

        $categories = $query->paginate(env('APP_PAGINATE_PER_PAGE', 10))
            ->appends([
                'search' => $search,
                'searchtype' => $searchtype,
                'searchstatus' => $searchstatus,
            ]);

        return view('gl.chart.category.index', compact('totalCategories', 'activeCategories', 'inactiveCategories', 'categories', 'accountCategories', 'accountTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required',
            'type' => 'required',
            'status' => 'required|integer'
        ]);

        account_category::create([
            'account_type_id' => $request->type,
            'description' => $request->description,
            'status' => $request->status,
            'created_by' => auth()->user()->id,
            'created_at' => now(),
        ]);

        return redirect()->route('gl.chart.category.index')->with('success', 'Account category successfully created.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'edit_description' => 'required',
            'edit_type' => 'required',
            'edit_status' => 'required|integer'
        ]);

        $category = account_category::findOrFail($id);

        $category->update([
            'account_type_id' => $request->edit_type,
            'description' => $request->edit_description,
            'status' => $request->edit_status,
            'updated_by' => auth()->user()->id,
            'updated_at' => now(),
        ]);

        return redirect()->route('gl.chart.category.index')->with('success', 'Account category successfully updated.');
    }
}
