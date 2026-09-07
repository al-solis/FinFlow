@extends('dashboard')

@section('title', 'Users')

@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Users</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage user accounts and their assigned role.</p>
                </div>
                <a href="{{ route('admin.users.create') }}"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    + New User
                </a>
            </div>

            <form method="GET" action="{{ route('admin.user') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <div class="relative flex-1 min-w-[220px]">
                    <input type="text" name="searchname" value="{{ request('searchname') }}"
                        placeholder="Search by name, employee ID, or email"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                </div>
                <div>
                    <select name="searchrole" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">All Roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(request('searchrole') == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    Filter
                </button>
                @if (request()->anyFilled(['searchname', 'searchrole']))
                    <a href="{{ route('admin.users.index') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Employee ID</th>
                            <th class="px-3 py-3 text-left">Name</th>
                            <th class="px-3 py-3 text-left">Email</th>
                            <th class="px-3 py-3 text-left">Role</th>
                            <th class="px-3 py-3 text-left">Department</th>
                            <th class="px-3 py-3 text-right w-16">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-800">{{ $user->employee_id }}</td>
                                <td class="px-3 py-3 text-gray-700">
                                    {{ $user->last_name }}, {{ $user->first_name }} {{ $user->middle_name }}
                                </td>
                                <td class="px-3 py-3 text-gray-500">{{ $user->email }}</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">
                                        {{ $user->role->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-gray-500">{{ $user->department_id ?? '—' }}</td>
                                <td class="px-3 py-3 text-right">
                                    <a href="{{ route('admin.users.edit', $user) }}" title="Edit User"
                                        class="text-gray-500 hover:text-blue-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293a.5.5 0 0 1 0 .707z" />
                                            <path
                                                d="M13.752 4.396l-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12z" />
                                            <path fill-rule="evenodd"
                                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }}</div>
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
