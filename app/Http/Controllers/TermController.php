<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\term;


class TermController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');

        $query = term::query();

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

        $terms = $query->paginate(config('app.paginate'))
            ->appends(['search' => $search, 'searchstatus' => $searchstatus]);

        return view('admin.term.index', compact('terms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|max:20|unique:terms,code',
            'name' => 'required|max:100',
            'description' => 'nullable',
            'days' => 'required|integer|min:0',
            'status' => 'required|boolean',
        ]);

        term::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'days' => $request->days,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.terms')->with('success', 'Term created successfully.');
    }

    public function update(Request $request, Term $term)
    {
        $request->validate([
            'edit_code' => 'required|max:20|unique:terms,code,' . $term->id,
            'edit_name' => 'required|max:100',
            'edit_description' => 'nullable',
            'edit_days' => 'required|integer|min:0',
            'edit_status' => 'required|boolean',
        ]);

        $term->update([
            'code' => $request->edit_code,
            'name' => $request->edit_name,
            'description' => $request->edit_description,
            'days' => $request->edit_days,
            'status' => $request->edit_status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.terms')->with('success', 'Term updated successfully.');
    }

}
