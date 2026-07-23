<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\account_structure;

class AccountStructureController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');

        $totalStructures = account_structure::count();
        $activeStructures = account_structure::where('status', '1')->count();
        $inactiveStructures = account_structure::where('status', '0')->count();

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

        $accountStructures = $query->paginate(env('APP_PAGINATE_PER_PAGE', 10))
            ->appends([
                'search' => $search,
                'searchstatus' => $searchstatus,
            ]);

        return view('setup.chart.account_structures.index', compact('totalStructures', 'activeStructures', 'inactiveStructures', 'accountStructures'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:60',
            'description' => 'required|max:120',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|integer'
        ]);

        account_structure::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'is_default' => $request->boolean('default'),
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('setup.chart.account_structures.index')
            ->with('success', 'Account structure created successfully.');
    }

    public function update(Request $request, $id)
    {
        $accountStructure = account_structure::findOrFail($id);

        $request->validate([
            'edit_name' => 'required|max:60',
            'edit_description' => 'required|max:120',
            'edit_start_date' => 'required|date',
            'edit_end_date' => 'required|date|after_or_equal:edit_start_date',
            'edit_status' => 'required|integer'
        ]);

        // dd($request->all());

        $accountStructure->update([
            'name' => $request->edit_name,
            'description' => $request->edit_description,
            'start_date' => $request->edit_start_date,
            'end_date' => $request->edit_end_date,
            'status' => $request->edit_status,
            'is_default' => $request->boolean('edit_default'),
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('setup.chart.account_structures.index')
            ->with('success', 'Account structure updated successfully.');
    }
}
