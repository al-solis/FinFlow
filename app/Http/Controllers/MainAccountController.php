<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\main_account;
use App\Models\account_type;
use App\Models\account_category;
use App\Models\account_subcategory;

class MainAccountController extends Controller
{
    public function index()
    {
        $search = request('search');
        $searchtype = request('searchtype');
        $searchstatus = request('searchstatus');
        $accountTypes = account_type::all();
        $accountCategories = account_category::all();
        $accountSubcategories = account_subcategory::all();

        $accounts = main_account::get();
        $totalAccounts = $accounts->count();
        $activeAccounts = $accounts->where('status', '1')->count();
        $inactiveAccounts = $accounts->where('status', '0')->count();
        $totalSubAccounts = $accountSubcategories->count();

        $query = main_account::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });

        }

        if ($searchtype) {
            $query->where('account_type_id', $searchtype);
        }
        if ($searchstatus != null) {
            $query->where('status', $searchstatus);
        }

        $mainAccounts = $query
            ->orderBy('code', 'asc')
            ->paginate(env('APP_PAGINATE_PER_PAGE', 10))
            ->appends([
                'search' => $search,
                'searchtype' => $searchtype,
                'searchstatus' => $searchstatus,
            ]);

        return view('gl.chart.index', compact('mainAccounts', 'accountTypes', 'accountCategories', 'accountSubcategories', 'totalAccounts', 'activeAccounts', 'inactiveAccounts', 'totalSubAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_code' => 'required|unique:main_accounts,code',
            'description' => 'required',
            'type' => 'required|exists:account_types,id',
            'category' => 'required|exists:account_categories,id',
            'subcategory' => 'required|exists:account_subcategories,id',
            'status' => 'required|boolean',
        ]);

        main_account::create([
            'code' => $request->account_code,
            'description' => $request->description,
            'account_type_id' => $request->type,
            'account_category_id' => $request->category,
            'account_subcategory_id' => $request->subcategory,
            'status' => $request->status,
            'created_by' => auth()->user()->id,
            'created_at' => now(),
        ]);

        return redirect()->route('gl.chart')->with('success', 'Main account created successfully.');
    }

    public function updateChart(Request $request, $id)
    {
        $account = main_account::findOrFail($id);
        $request->validate([
            'edit_account_code' => [
                'required',
                Rule::unique('main_accounts', 'code')->ignore($account->id)
            ],
            'edit_description' => 'required',
            'edit_type' => 'required|exists:account_types,id',
            'edit_category' => 'required|exists:account_categories,id',
            'edit_subcategory' => 'required|exists:account_subcategories,id',
            'edit_status' => 'required|boolean',
        ]);

        $account->update([
            'code' => $request->edit_account_code,
            'description' => $request->edit_description,
            'account_type_id' => $request->edit_type,
            'account_category_id' => $request->edit_category,
            'account_subcategory_id' => $request->edit_subcategory,
            'status' => $request->edit_status,
            'updated_by' => auth()->user()->id,
            'updated_at' => now(),
        ]);

        return redirect()->route('gl.chart')->with('success', 'Main account updated successfully.');
    }
}
