<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\SystemSettings;
use App\Models\segment;
use App\Models\account_structure;
use App\Services\ChartOfAccountsService;

class AccountStructureController extends Controller
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }
    public function index(Request $request)
    {
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');

        $totalStructures = account_structure::where('organization_id', $this->getOrganizationId())->count();
        $activeStructures = account_structure::where('status', '1')->where('organization_id', $this->getOrganizationId())->count();
        $inactiveStructures = account_structure::where('status', '0')->where('organization_id', $this->getOrganizationId())->count();

        $query = account_structure::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($searchstatus != null) {
            $query->where('status', $searchstatus);
        }

        $accountStructures = $query
            ->where('organization_id', $this->getOrganizationId())
            ->paginate(env('APP_PAGINATE_PER_PAGE', 10))
            ->appends([
                'search' => $search,
                'searchstatus' => $searchstatus,
            ]);

        return view('gl.chart.account_structures.index', compact('totalStructures', 'activeStructures', 'inactiveStructures', 'accountStructures'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:60',
            'description' => 'required|max:120',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        account_structure::create([
            'organization_id' => $this->getOrganizationId(),
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 0, // Default to Draft if not provided
            'is_default' => $request->boolean('default'),
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('gl.structure')
            ->with('success', 'Account structure created successfully.');
    }

    public function update(Request $request, $id)
    {
        $accountStructure = account_structure::findOrFail($id);

        // dd($request->all());
        $request->validate([
            'edit_name' => 'required|max:60',
            'edit_description' => 'required|max:120',
            'edit_start_date' => 'required|date',
            'edit_end_date' => 'required|date|after_or_equal:edit_start_date',
        ]);

        $accountStructure->update([
            'name' => $request->edit_name,
            'description' => $request->edit_description,
            'start_date' => $request->edit_start_date,
            'end_date' => $request->edit_end_date,
            'is_default' => $request->boolean('edit_default'),
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        //update other account structures to not default if this one is set to default
        if ($request->boolean('edit_default')) {
            account_structure::where('organization_id', $this->getOrganizationId())
                ->where('id', '!=', $accountStructure->id)
                ->update([
                    'is_default' => 0,
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);
        }

        return redirect()->route('gl.structure')
            ->with('success', 'Account structure updated successfully.');
    }

    public function sync(account_structure $accountStructure, ChartOfAccountsService $service)
    {
        // Validate the structure first
        $validation = $service->validateStructure($accountStructure);

        if (!$validation['valid']) {
            return redirect()->back()->with(
                'error',
                'Cannot generate chart of accounts. Please fix the following issues: ' .
                implode(', ', $validation['issues'])
            );
        }

        // Show warnings if any
        if (!empty($validation['warnings'])) {
            session()->flash('warning', 'Warnings: ' . implode(', ', $validation['warnings']));
        }

        // Get estimated count
        $estimatedCount = $service->estimateAccountCount($accountStructure);

        // If it's a new generation (not sync), show confirmation with count
        if ($estimatedCount > 1000) {
            return redirect()->back()->with(
                'warning',
                "This will generate approximately {$estimatedCount} accounts. " .
                "This might take a while. Proceed with caution."
            );
        }

        // Perform the generation/sync
        $result = $service->generateOrSync(
            $accountStructure,
            auth()->id(),
            request()->has('force') // Optional force regenerate flag
        );

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', 'Generation failed: ' . $result['message']);
        }
    }


}
