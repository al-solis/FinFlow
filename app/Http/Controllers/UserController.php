<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\role;
use App\Models\vendor;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function index(Request $request)
    {
        $users = User::query()
            ->with('role')
            ->when($request->filled('searchname'), fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('first_name', 'like', '%' . $request->searchname . '%')
                    ->orWhere('last_name', 'like', '%' . $request->searchname . '%')
                    ->orWhere('employee_id', 'like', '%' . $request->searchname . '%')
                    ->orWhere('email', 'like', '%' . $request->searchname . '%');
            }))
            ->when($request->filled('searchrole'), fn($q) => $q->where('role_id', $request->searchrole))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(config('app.paginate', 15))
            ->withQueryString();

        $roles = role::orderBy('name')->get();

        return view('admin.user.index', compact('users', 'roles'));
    }

    public function create()
    {
        return view('admin.user.form', [
            'targetUser' => new User(),
            'roles' => role::orderBy('name')->get(),
            'vendors' => vendor::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|max:10|unique:users,employee_id',
            'last_name' => 'required|string|max:50',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'email' => 'required|email|max:255|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|string|max:10',
            'vendor_code' => 'nullable|exists:vendors,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'employee_id' => $validated['employee_id'],
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'department_id' => $validated['department_id'] ?? null,
            'vendor_code' => $validated['vendor_code'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.user')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('admin.user.form', [
            'targetUser' => $user,
            'roles' => role::orderBy('name')->get(),
            'vendors' => vendor::with('category')
                ->whereHas('category', function ($query) {
                    $query->where('code', 'EMP')
                        ->where('is_active', true)
                        ->where('organization_id', $this->getOrganizationId());
                })
                ->where('is_active', true)
                ->where('organization_id', $this->getOrganizationId())
                ->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:10', Rule::unique('users', 'employee_id')->ignore($user->id)],
            'last_name' => 'required|string|max:50',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|string|max:10',
            'vendor_code' => 'nullable|exists:vendors,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'employee_id' => $validated['employee_id'],
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'department_id' => $validated['department_id'] ?? null,
            'vendor_code' => $validated['vendor_code'] ?? null,
        ];

        // Only touch the password if the admin actually typed a new one
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.user')->with('success', 'User updated.');
    }
}