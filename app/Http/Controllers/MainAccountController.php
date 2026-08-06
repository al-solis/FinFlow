<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\SystemSettings;
use App\Models\main_account;
use App\Models\account_type;
use App\Models\account_category;
use App\Models\account_subcategory;

class MainAccountController extends Controller
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }
    public function index()
    {
        $search = request('search');
        $searchtype = request('searchtype');
        $searchstatus = request('searchstatus');
        $accountTypes = account_type::where('organization_id', $this->getOrganizationId())
            ->orderBy('code')
            ->get();
        $accountCategories = account_category::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->get();
        $accountSubcategories = account_subcategory::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->get();

        $accounts = main_account::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->get();
        $totalAccounts = $accounts->where('organization_id', $this->getOrganizationId())->count();
        $activeAccounts = $accounts->where('organization_id', $this->getOrganizationId())->where('status', '1')->count();
        $inactiveAccounts = $accounts->where('organization_id', $this->getOrganizationId())->where('status', '0')->count();
        $totalSubAccounts = $accountSubcategories->where('organization_id', $this->getOrganizationId())->count();

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
            ->where('organization_id', $this->getOrganizationId())
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
        // dd($settings = SystemSettings::get());
        // dd($request->all());
        $request->validate([
            'account_code' => [
                'required',
                Rule::unique('main_accounts', 'code')->where(function ($query) {
                    return $query->where('organization_id', $this->getOrganizationId());
                })
            ],
            'description' => 'required',
            'type' => 'required|exists:account_types,id',
            'category' => 'required|exists:account_categories,id',
            'subcategory' => 'required|exists:account_subcategories,id',
            'status' => 'required|boolean',
        ]);

        main_account::create([
            'organization_id' => $this->getOrganizationId(),
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
                Rule::unique('main_accounts', 'code')->where(function ($query) use ($account) {
                    return $query->where('organization_id', $this->getOrganizationId());
                })->ignore($account->id)
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
