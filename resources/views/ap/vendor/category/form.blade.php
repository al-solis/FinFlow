@extends('dashboard')

@section('title', $isEdit ? 'Edit Vendor Category' : 'New Vendor Category')

@section('content')
    <div class="mx-auto max-w-4xl">
        @if ($errors->any())
            <div id="error-alert" class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 shadow-sm">
                <div class="font-semibold text-red-700 text-sm">Please correct the following errors:</div>
                <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $formAction = $isEdit ? route('ap.categories.update', $category) : route('ap.categories.store');
        @endphp

        <form method="POST" action="{{ $formAction }}"
            class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.128 1.5.384l5.5 3.5c.594.383 1 1.034 1 1.75V20a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5-5-5" />
                            </svg>
                            {{ $isEdit ? 'Edit Vendor Category' : 'New Vendor Category' }}
                        </h1>
                        <p class="mt-1 text-sm text-blue-100">
                            {{ $isEdit ? 'Update vendor category information' : 'Create a new vendor category for classification' }}
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('ap.categories') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 transition-all duration-200 backdrop-blur-sm border border-white/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back
                        </a>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $isEdit ? 'Update Category' : 'Save Category' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form Fields -->
            <div class="px-8 py-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code', $category->code) }}" required
                            maxlength="20" placeholder="e.g., SUPPLIER, EMPLOYEE"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('code')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select name="status"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="1" @selected(old('status', $category->status ?? 1))>Active</option>
                            <option value="0" @selected(old('status', $category->status ?? 1) === 0)>Inactive</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Name -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                            maxlength="100" placeholder="e.g., General Supplier, Employee Vendor"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea name="description" rows="3" maxlength="255" placeholder="Brief description of this vendor category"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ old('description', $category->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if ($isEdit)
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-xs text-gray-500">
                            <strong>Created:</strong> {{ $category->created_at->format('M d, Y g:i A') }}
                            @if ($category->updated_at)
                                <br><strong>Last Updated:</strong> {{ $category->updated_at->format('M d, Y g:i A') }}
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </form>
    </div>
@endsection
