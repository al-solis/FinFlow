@extends('dashboard')

@section('title', $targetUser->exists ? 'Edit User' : 'New User')

@section('content')
    <div class="mx-auto max-w-4xl">
        @if ($errors->any())
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 shadow-sm">
                <div class="font-semibold text-red-700 text-sm">Please correct the following errors:</div>
                <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $isEdit = $targetUser->exists;
            $formAction = $isEdit ? route('admin.users.update', $targetUser->id) : route('admin.users.store');
        @endphp

        <form method="POST" action="{{ $formAction }}">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ $isEdit ? 'Edit User' : 'New User' }}</h1>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ $isEdit ? 'Update this user account' : 'Create a new user account' }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('admin.user') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                                Back
                            </a>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">
                                {{ $isEdit ? 'Update User' : 'Save User' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-900">
                                Employee ID <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="employee_id" maxlength="10"
                                value="{{ old('employee_id', $targetUser->employee_id) }}" required
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <select name="role_id" required
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                                <option value="">Select role&hellip;</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('role_id', $targetUser->role_id) == $role->id)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">Department</label>
                            <input type="text" name="department_id" maxlength="10"
                                value="{{ old('department_id', $targetUser->department_id) }}"
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-900">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="last_name" maxlength="50"
                                value="{{ old('last_name', $targetUser->last_name) }}" required
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="first_name" maxlength="50"
                                value="{{ old('first_name', $targetUser->first_name) }}" required
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">Middle Name</label>
                            <input type="text" name="middle_name" maxlength="50"
                                value="{{ old('middle_name', $targetUser->middle_name) }}"
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-900">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email', $targetUser->email) }}" required
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">
                                Linked Vendor <span class="text-gray-400">(optional)</span>
                            </label>
                            <select name="vendor_code" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                                <option value="">— None —</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected(old('vendor_code', $targetUser->vendor_code) == $vendor->id)>
                                        {{ $vendor->name }} — {{ $vendor->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-900">
                                Password
                                @if ($isEdit)
                                    <span class="text-gray-400">(leave blank to keep current)</span>
                                @else
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="password" name="password" {{ $isEdit ? '' : 'required' }}
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">
                                Confirm Password
                                @if (!$isEdit)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="password" name="password_confirmation" {{ $isEdit ? '' : 'required' }}
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
