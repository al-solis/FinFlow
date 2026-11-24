<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettings;
use App\Constants\Modules;
use App\Models\account_category;
use App\Models\account_type;
use App\Traits\AuthorizesAccessRights;

class AccountCategoryController extends Controller
{
    use AuthorizesAccessRights;

    /**
     * Return the real organization_id, not the settings row's PK.
     */
    private function getOrganizationId(): ?int
    {
        $settings = SystemSettings::get();

        if (!$settings) {
            return null;
        }

        return $settings->organization_id ?? $settings->id ?? null;
    }

    public function index(Request $request)
    {
        $canCreate = $this->canCreate(Modules::GL, Modules::GL_CHART);
        $canUpdate = $this->canUpdate(Modules::GL, Modules::GL_CHART);
        $canDelete = $this->canDelete(Modules::GL, Modules::GL_CHART);

        $orgId = $this->getOrganizationId();
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');
        $searchtype = $request->input('searchtype');

        $accountTypes = account_type::where('organization_id', $orgId)
            ->orderBy('code')
            ->get();

        $accountCategories = account_category::where('organization_id', $orgId)
            ->where('status', 1)
            ->orderBy('account_type_id')
            ->get();

        $totalCategories = account_category::where('organization_id', $orgId)->count();
        $activeCategories = account_category::where('organization_id', $orgId)->where('status', '1')->count();
        $inactiveCategories = account_category::where('organization_id', $orgId)->where('status', '0')->count();

        $query = account_category::query();

        if ($search) {
            $query->where('description', 'like', "%{$search}%");
        }

        if ($searchtype) {
            $query->where('account_type_id', $searchtype);
        }

        if ($searchstatus !== null && $searchstatus !== '') {
            $query->where('status', $searchstatus);
        }

        $categories = $query
            ->where('organization_id', $orgId)
            ->paginate(env('APP_PAGINATE_PER_PAGE', 10))
            ->appends([
                'search' => $search,
                'searchtype' => $searchtype,
                'searchstatus' => $searchstatus,
            ]);

        return view('gl.chart.category.index', compact(
            'totalCategories',
            'activeCategories',
            'inactiveCategories',
            'categories',
            'accountCategories',
            'accountTypes',
            'canCreate',
            'canUpdate',
            'canDelete'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeCreate(Modules::GL, Modules::GL_CHART);

        $request->validate([
            'description' => 'required|string|max:255',
            'type' => 'required|integer',
            'status' => 'required|integer|in:0,1',
        ]);

        account_category::create([
            'organization_id' => $this->getOrganizationId(),
            'account_type_id' => $request->type,
            'description' => $request->description,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('gl.chart.category.index')
            ->with('success', 'Account category successfully created.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeUpdate(Modules::GL, Modules::GL_CHART);

        $request->validate([
            'edit_description' => 'required|string|max:255',
            'edit_type' => 'required|integer',
            'edit_status' => 'required|integer|in:0,1',
        ]);


        $category = account_category::where('organization_id', $this->getOrganizationId())
            ->findOrFail($id);

        $category->update([
            'account_type_id' => $request->edit_type,
            'description' => $request->edit_description,
            'status' => $request->edit_status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('gl.chart.category.index')
            ->with('success', 'Account category successfully updated.');
    }
}