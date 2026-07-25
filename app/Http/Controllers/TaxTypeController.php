<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\tax_type;

class TaxTypeController extends Controller
{
    public function index(Request $request)
    {
        $taxTypes = tax_type::query();

        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');

        if ($search) {
            $search = $request->input('search');
            $taxTypes->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($searchstatus !== null) {
            $searchstatus = $request->input('searchstatus');
            $taxTypes->where('status', $searchstatus);
        }


        $taxTypes = $taxTypes->orderBy('code')->paginate(config('app.paginate'));

        return view('tax.tax_type.index', compact('taxTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:tax_types,code',
            'name' => 'required',
            'description' => 'nullable',
        ]);

        tax_type::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 1, // Default to active if not provided
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('tax.ty')->with('success', 'Tax type created successfully.');
    }

    public function update(Request $request, $id)
    {
        $taxType = tax_type::findOrFail($id);

        $request->validate([
            'edit_code' => 'required|unique:tax_types,code,' . $taxType->id,
            'edit_name' => 'required',
            'edit_description' => 'nullable',
            'edit_status' => 'required|in:0,1',
        ]);

        $taxType->update([
            'code' => $request->edit_code,
            'name' => $request->edit_name,
            'description' => $request->edit_description,
            'status' => $request->edit_status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('tax.ty')->with('success', 'Tax type updated successfully.');
    }
}
