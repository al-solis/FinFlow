<?php

namespace App\Http\Controllers;

use App\Models\role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = role::withCount('users')
            ->when($request->filled('searchname'), fn($q) => $q->where('name', 'like', '%' . $request->searchname . '%'))
            ->orderBy('name')
            ->paginate(config('app.paginate', 15))
            ->withQueryString();

        return view('admin.role.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.role.form', [
            'targetRole' => new role(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:255',
        ]);

        role::create($validated);

        return redirect()->route('admin.role')->with('success', 'Role created.');
    }

    public function edit(role $role)
    {
        return view('admin.role.form', [
            'targetRole' => $role,
        ]);
    }

    public function update(Request $request, role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:255',
        ]);

        $role->update($validated);

        return redirect()->route('admin.role')->with('success', 'Role updated.');
    }
}