<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\tax_master;
use App\Models\tax_type;
use App\Models\tax_formula;

class TaxMasterController extends Controller
{
    public function index(Request $request)
    {
        $taxTypes = tax_type::where('status', 1)
            ->orderBy('code')
            ->get();
        $taxFormulas = tax_formula::where('status', 1)
            ->orderBy('code')
            ->get();

        $search = $request->input('search');
        $searchtype = $request->input('searchtype');
        $searchformula = $request->input('searchformula');
        $searchstatus = $request->input('searchstatus');

        $taxMasters = tax_master::query();

        if ($search) {
            $taxMasters->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($searchtype != null) {
            $taxMasters->where('tax_type_id', $searchtype);
        }

        if ($searchformula != null) {
            $taxMasters->where('tax_formula_id', $searchformula);
        }

        if ($searchstatus !== null) {
            $taxMasters->where('status', $searchstatus);
        }

        $taxMasters = $taxMasters->orderBy('code')
            ->paginate(config('app.paginate'))
            ->appends([
                'search' => $search,
                'searchtype' => $searchtype,
                'searchformula' => $searchformula,
                'searchstatus' => $searchstatus,
            ]);

        return view('tax.tax_master.index', compact('taxMasters', 'taxTypes', 'taxFormulas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:tax_masters,code',
            'name' => 'required|string|max:100',
            'tax_type_id' => 'required|exists:tax_types,id',
            'tax_formula_id' => 'required|exists:tax_formulas,id',
            'rate' => 'nullable|numeric|min:0',
            'fixed_amount' => 'nullable|numeric|min:0',
            'gl_account_code' => 'nullable|string|max:20',
            'recoverable' => 'nullable|boolean',
            'priority' => 'required|integer|min:1',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|in:0,1',
        ]);

        tax_master::create([
            'code' => $request->code,
            'name' => $request->name,
            'tax_type_id' => $request->tax_type_id,
            'tax_formula_id' => $request->tax_formula_id,
            'rate' => $request->rate,
            'fixed_amount' => $request->fixed_amount,
            'gl_account_code' => $request->gl_account_code,
            'recoverable' => $request->recoverable ? true : false,
            'priority' => $request->priority,
            'effective_from' => $request->effective_from,
            'effective_to' => $request->effective_to,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('tax.tm')->with('success', 'Tax Master created successfully.');
    }

    public function update(Request $request, $id)
    {
        $taxMaster = tax_master::findOrFail($id);

        $request->validate([
            'edit_code' => 'required|string|max:20|unique:tax_masters,code,' . $taxMaster->id,
            'edit_name' => 'required|string|max:100',
            'edit_tax_type_id' => 'required|exists:tax_types,id',
            'edit_tax_formula_id' => 'required|exists:tax_formulas,id',
            'edit_rate' => 'nullable|numeric|min:0',
            'edit_fixed_amount' => 'nullable|numeric|min:0',
            'edit_gl_account_code' => 'nullable|string|max:20',
            'edit_recoverable' => 'nullable|boolean',
            'edit_priority' => 'required|integer|min:1',
            'edit_effective_from' => 'required|date',
            'edit_effective_to' => 'nullable|date|after_or_equal:effective_from',
            'edit_status' => 'required|in:0,1',
        ]);

        $taxMaster->update([
            'code' => $request->edit_code,
            'name' => $request->edit_name,
            'tax_type_id' => $request->edit_tax_type_id,
            'tax_formula_id' => $request->edit_tax_formula_id,
            'rate' => $request->edit_rate,
            'fixed_amount' => $request->edit_fixed_amount,
            'gl_account_code' => $request->edit_gl_account_code,
            'recoverable' => $request->edit_recoverable ? true : false,
            'priority' => $request->edit_priority,
            'effective_from' => $request->edit_effective_from,
            'effective_to' => $request->edit_effective_to,
            'status' => $request->edit_status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('tax.tm')->with('success', 'Tax Master updated successfully.');
    }
}
