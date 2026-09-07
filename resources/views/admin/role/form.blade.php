@extends('dashboard')

@section('title', $targetRole->exists ? 'Edit Role' : 'New Role')

@section('content')
    <div class="mx-auto max-w-xl">
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
            $isEdit = $targetRole->exists;
            $formAction = $isEdit ? route('admin.roles.update', $targetRole->id) : route('admin.roles.store');
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
                            <h1 class="text-2xl font-bold text-white">{{ $isEdit ? 'Edit Role' : 'New Role' }}</h1>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ $isEdit ? 'Update this role' : 'Define a new role' }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('admin.role') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                                Back
                            </a>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">
                                {{ $isEdit ? 'Update Role' : 'Save Role' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-900">
                            Role Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $targetRole->name) }}" required
                            class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-900">Description</label>
                        <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm">{{ old('description', $targetRole->description) }}</textarea>
                    </div>

                    @if ($isEdit)
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-500">
                            To configure what this role can see and do, use
                            <a href="{{ route('admin.access', ['role_id' => $targetRole->id]) }}"
                                class="text-blue-600 hover:underline">Access Rights</a> after saving.
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
@endsection
