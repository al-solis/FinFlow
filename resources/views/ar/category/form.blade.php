@extends('dashboard')

@section('title', $category->exists ? 'Edit Customer Category' : 'New Customer Category')

@section('content')
    @php
        $isEdit = $category->exists;
        $formAction = $isEdit ? route('ar.categories.update', $category->id) : route('ar.categories.store');
    @endphp

    <div class="mx-auto max-w-2xl">
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

        <form method="POST" action="{{ $formAction }}">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">
                                {{ $isEdit ? 'Edit Customer Category' : 'New Customer Category' }}</h1>
                            <p class="mt-1 text-sm text-blue-100">Create or update a category for classifying
                                customers</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('ar.catg') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                                Back
                            </a>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">
                                {{ $isEdit ? 'Update Category' : 'Save Category' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-900">
                                Code <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="code" maxlength="20" required placeholder="e.g., PRV, GOV"
                                value="{{ old('code', $category->code) }}"
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select name="status" required
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                                <option value="1" @selected((string) old('status', $category->status) === '1')>Active</option>
                                <option value="0" @selected((string) old('status', $category->status) === '0')>Inactive</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-900">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" maxlength="100" required
                                placeholder="e.g., Government Agency" value="{{ old('name', $category->name) }}"
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-900">Description</label>
                            <textarea name="description" rows="3" placeholder="Brief description of this customer category"
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs">{{ old('description', $category->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
