@extends('dashboard')
@section('title', 'Vendor Master')
@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div id="alert-message"
                class="mt-5 mb-5 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800 shadow-sm transition-all duration-500">
                <div class="flex items-center">
                    <svg class="h-5 w-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div id="alert-message" class="mt-5 mb-5 rounded-lg border border-red-300 bg-red-50 p-4 shadow-sm">
                <div class="flex items-start">
                    <svg class="h-5 w-5 mr-2 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <div class="font-semibold text-red-700">
                            Please correct the following errors:
                        </div>
                        <ul class="mt-2 list-disc list-inside text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        @php
            $isEdit = isset($vendor) && $vendor->exists;
            $formAction = $isEdit ? route('ap.vendors.update', $vendor->id) : route('ap.vendors.store');
        @endphp

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Vendor Master
                            </h1>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ isset($vendor) && $vendor->exists ? 'Update supplier information' : 'Maintain supplier information used throughout the Accounts Payable module.' }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('ap.vendors') }}"
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                {{ isset($vendor) && $vendor->exists ? 'Update Vendor' : 'Save Vendor' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 bg-gray-50/50 px-4">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="vendorTabs"
                        data-tabs-toggle="#vendorTabContent" role="tablist">
                        <li class="me-1" role="presentation">
                            <button
                                class="inline-block rounded-t-lg border-b-2 border-transparent px-5 py-3 text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-all duration-200"
                                id="general-tab" data-tabs-target="#general" type="button" role="tab">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    General
                                </span>
                            </button>
                        </li>
                        <li class="me-1" role="presentation">
                            <button
                                class="inline-block rounded-t-lg border-b-2 border-transparent px-5 py-3 text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-all duration-200"
                                id="tax-tab" data-tabs-target="#tax" type="button" role="tab">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                    </svg>
                                    Tax
                                </span>
                            </button>
                        </li>
                        <li class="me-1" role="presentation">
                            <button
                                class="inline-block rounded-t-lg border-b-2 border-transparent px-5 py-3 text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-all duration-200"
                                id="address-tab" data-tabs-target="#address" type="button" role="tab">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Address
                                </span>
                            </button>
                        </li>
                        <li class="me-1" role="presentation">
                            <button
                                class="inline-block rounded-t-lg border-b-2 border-transparent px-5 py-3 text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-all duration-200"
                                id="contact-tab" data-tabs-target="#contact" type="button" role="tab">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Contact
                                </span>
                            </button>
                        </li>
                        <li class="me-1" role="presentation">
                            <button
                                class="inline-block rounded-t-lg border-b-2 border-transparent px-5 py-3 text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-all duration-200"
                                id="financial-tab" data-tabs-target="#financial" type="button" role="tab">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Financial
                                </span>
                            </button>
                        </li>
                        <li class="me-1" role="presentation">
                            <button
                                class="inline-block rounded-t-lg border-b-2 border-transparent px-5 py-3 text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-all duration-200"
                                id="bank-tab" data-tabs-target="#bank" type="button" role="tab">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Banking
                                </span>
                            </button>
                        </li>
                        <li role="presentation">
                            <button
                                class="inline-block rounded-t-lg border-b-2 border-transparent px-5 py-3 text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-all duration-200"
                                id="more-tab" data-tabs-target="#more" type="button" role="tab">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                    More
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div id="vendorTabContent">
                    <!-- GENERAL TAB -->
                    <div id="general" role="tabpanel" class="space-y-6 p-8">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Vendor Code -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Vendor Code
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="vendor_code" name="vendor_code"
                                    value="{{ old('vendor_code', $vendor->code ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500 required">
                            </div>

                            <!-- Vendor Name -->
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Vendor Name
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="vendor_name" name="vendor_name"
                                    value="{{ old('vendor_name', $vendor->name ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                            <!-- Status -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Status
                                </label>
                                <select name="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                            <!-- Legal Name -->
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Legal Name
                                </label>
                                <input type="text" id="legal_name" name="legal_name"
                                    value="{{ old('legal_name', $vendor->legal_name ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                            <!-- Vendor Category -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Vendor Category
                                    <span class="text-red-500">*</span>
                                </label>
                                <select name="vendor_category_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Category --</option>
                                    @foreach ($vendorCategories as $category)
                                        <option
                                            value="{{ $category->id }}"{{ old('vendor_category_id', $vendor->vendor_category_id ?? '') == $category->id ? ' selected' : '' }}>
                                            {{ $category->code }} - {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Vendor Type -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Vendor Type
                                </label>
                                <select name="vendor_type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="Company"
                                        {{ old('vendor_type', $vendor->vendor_type ?? '') == 'Company' ? ' selected' : '' }}>
                                        Company</option>
                                    <option value="Individual"
                                        {{ old('vendor_type', $vendor->vendor_type ?? '') == 'Individual' ? ' selected' : '' }}>
                                        Individual</option>
                                    <option value="Government"
                                        {{ old('vendor_type', $vendor->vendor_type ?? '') == 'Government' ? ' selected' : '' }}>
                                        Government</option>
                                </select>
                            </div>

                            <!-- Industry -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Industry
                                </label>
                                <input type="text" name="industry"
                                    value="{{ old('industry', $vendor->industry ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                            <!-- Currency -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Currency
                                </label>
                                <select disabled name="currency_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Currency --</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ old('currency_id', $vendor->currency_id ?? '') == $currency->id ? ' selected' : '' }}>
                                            {{ $currency->code }}
                                            @if (!empty($currency->description))
                                                - {{ $currency->description }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Payment Term -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Default Payment Term
                                </label>
                                <select disabled name="payment_term_id_default"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500 read-only:">
                                    <option value="">-- Select Payment Term --</option>
                                    @foreach ($paymentTerms as $term)
                                        <option value="{{ $term->id }}"
                                            {{ old('payment_term_id', $vendor->payment_term_id ?? '') == $term->id ? ' selected' : '' }}>
                                            {{ $term->code }} - {{ $term->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Preferred Language -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Preferred Language
                                </label>
                                <select name="language"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="English"
                                        {{ old('language', $vendor->language ?? '') == 'English' ? ' selected' : '' }}>
                                        English</option>
                                    <option value="Filipino"
                                        {{ old('language', $vendor->language ?? '') == 'Filipino' ? ' selected' : '' }}>
                                        Filipino</option>
                                </select>
                            </div>
                        </div>
                        <!-- Vendor Notes -->
                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Vendor Notes
                            </label>
                            <textarea name="remarks" rows="4"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('remarks', $vendor->remarks ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- TAX TAB -->
                    <div class="hidden space-y-6 p-8" id="tax" role="tabpanel">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <!-- TIN -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Tax Identification No. (TIN)
                                </label>
                                <input type="text" name="tax_id" value="{{ old('tax_id', $vendor->tax_id ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                            <!-- Branch Code -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Tax Branch Code
                                </label>
                                <input type="text" name="tax_branch_code"
                                    value="{{ old('tax_branch_code', $vendor->tax_branch_code ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Registration Number -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    SEC / DTI Registration No.
                                </label>
                                <input type="text" name="registration_no"
                                    value="{{ old('registration_no', $vendor->registration_no ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Tax Group -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Tax Group
                                </label>
                                <select name="tax_group_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Tax Group --</option>
                                    @foreach ($taxGroups as $taxGroup)
                                        <option value="{{ $taxGroup->id }}"
                                            {{ old('tax_group_id', $vendor->tax_group_id ?? '') == $taxGroup->id ? ' selected' : '' }}>
                                            {{ $taxGroup->code }} - {{ $taxGroup->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- VAT Registered -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    VAT Registration
                                </label>
                                <select name="vat_registered"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1"
                                        {{ old('vat_registered', $vendor->vat_registered ?? 1) == 1 ? ' selected' : '' }}>
                                        VAT Registered</option>
                                    <option value="0"
                                        {{ old('vat_registered', $vendor->vat_registered ?? 0) == 0 ? ' selected' : '' }}>
                                        Non-VAT</option>
                                </select>
                            </div>

                            <!-- Withholding Tax -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Subject to Withholding Tax
                                </label>
                                <select name="subject_to_withholding"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1"
                                        {{ old('subject_to_withholding', $vendor->subject_to_withholding ?? 1) == 1 ? ' selected' : '' }}>
                                        Yes</option>
                                    <option value="0"
                                        {{ old('subject_to_withholding', $vendor->subject_to_withholding ?? 0) == 0 ? ' selected' : '' }}>
                                        No</option>
                                </select>
                            </div>

                            <!-- Withholding Tax -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Default Withholding Tax
                                </label>
                                <select name="withholding_tax_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Withholding Tax --</option>
                                    @foreach ($withholdingTaxes as $tax)
                                        <option value="{{ $tax->id }}"
                                            {{ old('withholding_tax_id', $vendor->withholding_tax_id ?? '') == $tax->id ? ' selected' : '' }}>
                                            {{ $tax->code }} - {{ $tax->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- VAT Tax -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Default VAT Tax
                                </label>
                                <select name="vat_tax_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select VAT Tax --</option>
                                    @foreach ($vatTaxes as $tax)
                                        <option value="{{ $tax->id }}"
                                            {{ old('vat_tax_id', $vendor->vat_tax_id ?? '') == $tax->id ? ' selected' : '' }}>
                                            {{ $tax->code }} - {{ $tax->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Tax Notes -->
                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Tax Remarks
                            </label>
                            <textarea name="tax_remarks" rows="4"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('tax_remarks', $vendor->tax_remarks ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- ADDRESS TAB -->
                    <div id="address" role="tabpanel" class="hidden space-y-6 p-8">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Address Line 1 -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Address Line 1
                                    {{-- <span class="text-red-500">*</span> --}}
                                </label>
                                <input type="text" name="address1"
                                    value="{{ old('address1', $vendor->address1 ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Address Line 2 -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Address Line 2
                                </label>
                                <input type="text" name="address2"
                                    value="{{ old('address2', $vendor->address2 ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- City -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    City / Municipality
                                    {{-- <span class="text-red-500">*</span> --}}
                                </label>
                                <input type="text" name="city" value="{{ old('city', $vendor->city ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Province -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Province / State
                                </label>
                                <input type="text" name="province"
                                    value="{{ old('province', $vendor->province ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Country -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Country
                                    {{-- <span class="text-red-500">*</span> --}}
                                </label>
                                <select name="country"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Country --</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->name }}"
                                            {{ old('country', $vendor->country ?? '') == $country->name ? ' selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- ZIP Code -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    ZIP / Postal Code
                                </label>
                                <input type="text" name="zip_code"
                                    value="{{ old('zip_code', $vendor->zip_code ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                        </div>
                    </div>

                    <!-- CONTACT TAB -->
                    <div id="contact" role="tabpanel" class="hidden space-y-6 p-8">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <!-- Contact Person -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Contact Person
                                </label>
                                <input type="text" name="contact_person"
                                    value="{{ old('contact_person', $vendor->contact_person ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Position -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Position
                                </label>
                                <input type="text" name="position"
                                    value="{{ old('position', $vendor->position ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Email Address
                                </label>
                                <input type="email" name="email" value="{{ old('email', $vendor->email ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Telephone No.
                                </label>
                                <input type="text" name="phone" value="{{ old('phone', $vendor->phone ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Mobile -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Mobile No.
                                </label>
                                <input type="text" name="mobile" value="{{ old('mobile', $vendor->mobile ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Website -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Website
                                </label>
                                <input type="url" name="website"
                                    value="{{ old('website', $vendor->website ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Contact Notes
                            </label>
                            <textarea name="contact_notes" rows="4"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('contact_notes', $vendor->contact_notes ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- FINANCIAL TAB -->
                    <div id="financial" role="tabpanel" class="hidden space-y-6 p-8">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Currency -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Currency
                                </label>
                                @php
                                    $selectedCurrencyId =
                                        isset($vendor) && $vendor->exists
                                            ? $vendor->currency_id ?? ''
                                            : $settings->currency_id ?? '';
                                @endphp
                                <select name="currency_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Currency --</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ old('currency_id', $selectedCurrencyId) == $currency->id ? ' selected' : '' }}>
                                            {{ $currency->code }} - {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Payment Term -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Payment Term
                                </label>
                                <select name="payment_term_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Payment Term --</option>
                                    @foreach ($paymentTerms as $term)
                                        <option value="{{ $term->id }}"
                                            {{ old('payment_term_id', $vendor->payment_term_id ?? '') == $term->id ? ' selected' : '' }}>
                                            {{ $term->code }} - {{ $term->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Payment Method
                                </label>
                                <select name="payment_method_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Payment Method --</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->id }}"
                                            {{ old('payment_method_id', $vendor->payment_method_id ?? '') == $method->id ? ' selected' : '' }}>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- AP Control Account -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    AP Control Account
                                    <span class="text-red-500">*</span>
                                </label>
                                <select name="default_ap_chart_of_account_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
                                    required>
                                    <option value="">-- Select GL Account --</option>
                                    @foreach ($apAccounts as $account)
                                        <option value="{{ $account->id }}"
                                            {{ old('default_ap_chart_of_account_id', $vendor->default_ap_chart_of_account_id ?? '') == $account->id ? ' selected' : '' }}>
                                            {{ $account->account_code }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Credit Limit -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Credit Limit
                                </label>
                                <input type="number" step="0.01" min="0" name="credit_limit"
                                    value="{{ old('credit_limit', $vendor->credit_limit ?? 0) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Default Discount -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Default Discount (%)
                                </label>
                                <input type="number" step="0.01" min="0" name="discount_percent"
                                    value="{{ old('discount_percent', $vendor->discount_percent ?? 0) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Preferred Payment Day -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Preferred Payment Day
                                </label>
                                <input type="number" min="1" max="31" name="preferred_payment_day"
                                    value="{{ old('preferred_payment_day', $vendor->preferred_payment_day ?? '') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Payment Priority -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Payment Priority
                                </label>
                                <select name="payment_priority"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="Low">Low</option>
                                    <option value="Normal"
                                        {{ old('payment_priority', $vendor->payment_priority ?? '') == 'Normal' ? ' selected' : '' }}>
                                        Normal</option>
                                    <option value="High"
                                        {{ old('payment_priority', $vendor->payment_priority ?? '') == 'High' ? ' selected' : '' }}>
                                        High</option>
                                    <option value="Critical"
                                        {{ old('payment_priority', $vendor->payment_priority ?? '') == 'Critical' ? ' selected' : '' }}>
                                        Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- BANK ACCOUNTS TAB -->
                    <div id="bank" role="tabpanel" class="hidden space-y-6 p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Vendor Bank Accounts
                                </h3>
                                <p class="text-sm text-gray-500">
                                    A vendor may have multiple bank accounts.
                                </p>
                            </div>

                            <button type="button" id="btnAddBank"
                                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-all duration-200 shadow-md hover:shadow-lg">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Add Bank Account
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="min-w-full text-xs text-gray-700">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Bank</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Branch</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Account Name</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Account Number</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">SWIFT</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Currency</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Primary</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-600" width="120">
                                            Action</th>
                                    </tr>
                                </thead>
                                <tbody id="bankTableBody" class="divide-y divide-gray-200">
                                    <!-- Bank rows will be added here dynamically -->
                                </tbody>
                            </table>
                        </div>
                        <div id="noBankMessage" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2">No bank accounts added yet.</p>
                            <p class="text-sm">Click <button type="button" id="btnAddBankText"
                                    class="text-blue-600 hover:underline font-medium">here</button> to add one.</p>
                            </p>
                        </div>
                    </div>


                    <!-- MORE TAB -->
                    <div id="more" role="tabpanel" class="hidden space-y-6 p-8">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Requires Purchase Order -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Requires Purchase Order
                                </label>
                                <select name="requires_po"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1" {{ old('requires_po') === '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('requires_po') === '0' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>

                            <!-- Lead Time -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Lead Time (Days)
                                </label>
                                <input type="number" min="0" name="lead_time"
                                    value="{{ old('lead_time', $vendor->lead_time ?? null) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Status
                                </label>
                                <select name="is_active"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1" {{ old('is_active', 1) === '1' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactive
                                    </option>
                                </select>
                            </div>

                            <!-- Preferred Vendor -->
                            <div>
                                <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                    Preferred Vendor
                                </label>
                                <select name="preferred_vendor"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="0" {{ old('preferred_vendor') === '0' ? 'selected' : '' }}>No
                                    </option>
                                    <option value="1" {{ old('preferred_vendor') === '1' ? 'selected' : '' }}>Yes
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div>
                            <label class="block text-xs font-medium text-gray-900 dark:text-white">
                                Internal Remarks
                            </label>
                            <textarea name="remarks" rows="5"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('remarks', $vendor->remarks ?? null) }}</textarea>
                        </div>

                        <!-- Attachments -->
                        <div>
                            <h3 class="mb-3 text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                Attachments
                                <span class="text-xs text-gray-500 font-normal">(Max 3MB per file)</span>
                            </h3>

                            <div
                                class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 p-10 text-center hover:border-blue-400 transition-all duration-200">
                                <svg class="mx-auto mb-4 h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16V8a5 5 0 0110 0v8a3 3 0 11-6 0V9" />
                                </svg>
                                <p class="text-sm text-gray-600 font-medium">
                                    Upload supporting documents
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP • Max 3MB each
                                </p>
                                <input type="file" name="attachments[]" multiple
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip" data-max-size="3072"
                                    class="mt-6 block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100 transition-all duration-200">
                            </div>

                            <!-- Display existing attachments -->
                            @if (isset($vendor) && $vendor->attachments && $vendor->attachments->count() > 0)
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Uploaded Files
                                        ({{ $vendor->attachments->count() }})</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach ($vendor->attachments as $attachment)
                                            <div
                                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="flex-shrink-0">
                                                        @php
                                                            $ext = pathinfo(
                                                                $attachment->original_name,
                                                                PATHINFO_EXTENSION,
                                                            );
                                                            $icon = match (strtolower($ext)) {
                                                                'pdf' => 'text-red-500',
                                                                'doc', 'docx' => 'text-blue-500',
                                                                'xls', 'xlsx' => 'text-green-500',
                                                                'jpg', 'jpeg', 'png' => 'text-purple-500',
                                                                'zip' => 'text-yellow-500',
                                                                default => 'text-gray-400',
                                                            };
                                                        @endphp
                                                        <svg class="h-6 w-6 {{ $icon }}" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                        </svg>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-700 truncate">
                                                            {{ $attachment->original_name }}
                                                        </p>
                                                        <p class="text-xs text-gray-500">
                                                            {{ number_format($attachment->file_size / 1024, 1) }} KB •
                                                            {{ $attachment->created_at->format('M d, Y') }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex gap-2 flex-shrink-0">
                                                    <a href="{{ asset('storage/' . $attachment->file_path) }}"
                                                        target="_blank"
                                                        class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                                        View
                                                    </a>
                                                    <button type="button"
                                                        class="text-red-600 hover:text-red-800 text-xs font-medium delete-attachment"
                                                        data-id="{{ $attachment->id }}"
                                                        data-name="{{ $attachment->original_name }}">
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Audit -->
                        <div>
                            <h3 class="mb-3 text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Audit Information
                            </h3>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label class="mb-2 block text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created By
                                    </label>
                                    <input type="text" readonly
                                        value="{{ isset($vendor) ? optional($vendor->createdBy)->first_name . ' ' . optional($vendor->createdBy)->last_name : '' }}"
                                        class="w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-xs text-gray-600 cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created At
                                    </label>
                                    <input type="text" readonly
                                        value="{{ isset($vendor) && $vendor->created_at ? $vendor->created_at->format($settings->date_format) : '' }}"
                                        class="w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-xs text-gray-600 cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Updated By
                                    </label>
                                    <input type="text" readonly
                                        value="{{ isset($vendor) ? optional($vendor->updatedBy)->first_name . ' ' . optional($vendor->updatedBy)->last_name : '' }}"
                                        class="w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-xs text-gray-600 cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Updated At
                                    </label>
                                    <input type="text" readonly
                                        value="{{ isset($vendor) && $vendor->updated_at ? $vendor->updated_at->format($settings->date_format) : '' }}"
                                        class="w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-xs text-gray-600 cursor-not-allowed">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the button and table body
            const btnAdd = document.getElementById('btnAddBank');
            const tbody = document.getElementById('bankTableBody');
            let rowIndex = 0;
            let isEditMode = @json(isset($vendor) && $vendor->exists);

            // Function to add a bank row
            function addBankRow(data = {}, index = null) {
                if (!tbody) return;

                const rowIndexToUse = index !== null ? index : rowIndex;
                const isPrimary = data.is_primary || false;

                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50 transition-colors duration-150";
                tr.innerHTML = `
                <td class="px-4 py-3">
                    <input type="text" name="banks[${rowIndexToUse}][bank_name]" 
                        value="${data.bank_name || ''}"
                        placeholder="Enter bank name"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5">
                </td>
                <td class="px-4 py-3">
                    <input type="text" name="banks[${rowIndexToUse}][branch]" 
                        value="${data.branch || ''}"
                        placeholder="Enter branch"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5">
                </td>
                <td class="px-4 py-3">
                    <input type="text" name="banks[${rowIndexToUse}][account_name]" 
                        value="${data.account_name || ''}"
                        placeholder="Enter account name"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5">
                </td>
                <td class="px-4 py-3">
                    <input type="text" name="banks[${rowIndexToUse}][account_number]" 
                        value="${data.account_number || ''}"
                        placeholder="Enter account number"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5">
                </td>
                <td class="px-4 py-3">
                    <input type="text" name="banks[${rowIndexToUse}][swift_code]" 
                        value="${data.swift_code || ''}"
                        placeholder="Enter SWIFT code"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5">
                </td>
                <td class="px-4 py-3">
                    <select name="banks[${rowIndexToUse}][currency_id]" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5">
                        <option value="">Select Currency</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}" ${data.currency_id == {{ $currency->id }} ? 'selected' : ''}>
                                {{ $currency->code }} - {{ $currency->name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td class="px-4 py-3 text-center">
                    <input type="checkbox" name="banks[${rowIndexToUse}][is_primary]" value="1"
                        ${isPrimary ? 'checked' : ''}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 cursor-pointer rounded">
                </td>
                <td class="px-4 py-3 text-center">
                    <button type="button" class="remove-bank-row inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                        Remove
                    </button>
                </td>
            `;

                tbody.appendChild(tr);

                // Only increment if no index was provided (new row)
                if (index === null) {
                    rowIndex++;
                }

                // Hide no bank message
                const noBankMsg = document.getElementById('noBankMessage');
                if (noBankMsg) noBankMsg.style.display = 'none';
            }

            // Add click event to button
            if (btnAdd) {
                btnAdd.addEventListener('click', function(e) {
                    e.preventDefault();
                    addBankRow();
                });
            }

            const btnAddText = document.getElementById('btnAddBankText');
            if (btnAddText) {
                btnAddText.addEventListener('click', function(e) {
                    e.preventDefault();
                    addBankRow();
                });
            }

            // Remove row functionality
            if (tbody) {
                tbody.addEventListener('click', function(e) {
                    if (e.target.closest('.remove-bank-row')) {
                        const tr = e.target.closest('tr');
                        tr.style.transition = 'all 0.3s ease';
                        tr.style.opacity = '0';
                        tr.style.transform = 'translateX(-20px)';
                        setTimeout(() => {
                            tr.remove();
                            const noBankMsg = document.getElementById('noBankMessage');
                            if (noBankMsg && tbody.children.length === 0) {
                                noBankMsg.style.display = 'block';
                            }
                        }, 300);
                    }
                });
            }

            // Load banks - Check for old input first (for validation errors), then existing banks
            const savedBanks = @json(old('banks', []));
            const existingBanks = @json(isset($vendor) ? $vendor->bankAccounts : []);

            if (savedBanks.length > 0) {
                // Use saved banks from old input (validation errors)
                savedBanks.forEach((bank, index) => {
                    // Check if this bank has data or if we should preserve the index
                    if (bank.bank_name || bank.account_number) {
                        addBankRow(bank, index);
                    }
                });
                // If no banks were added from saved data, add empty row
                if (tbody.children.length === 0) {
                    addBankRow();
                }
            } else if (existingBanks.length > 0) {
                // Load existing banks from database (edit mode)
                existingBanks.forEach((bank, index) => {
                    addBankRow(bank, index);
                });
            } else {
                // Add one empty row for new vendor
                addBankRow();
            }

            // ============================================
            // ATTACHMENT HANDLING
            // ============================================

            // === ATTACHMENT DELETION ===
            // Get the vendor ID from PHP
            const vendorId = @json(isset($vendor) ? $vendor->id : 0);

            // Use event delegation for delete buttons
            document.addEventListener('click', function(e) {
                const deleteBtn = e.target.closest('.delete-attachment');
                const attachmentFile = deleteBtn ? deleteBtn.dataset.name : null;
                if (!deleteBtn) return;
                e.preventDefault();

                const attachmentId = deleteBtn.dataset.id;
                if (!attachmentId) {
                    window.alert('Attachment ID not found');
                    return;
                }

                if (!window.confirm('Are you sure you want to delete ' + attachmentFile + ' file?')) {
                    return;
                }

                if (!vendorId) {
                    window.alert('Vendor ID not found. Please refresh the page and try again.');
                    return;
                }

                // Save original text for restore
                const originalHtml = deleteBtn.innerHTML;
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<span class="inline-block animate-spin mr-1">⟳</span> Deleting...';

                // Build the URL
                const deleteUrl = `/ap/vendor/${vendorId}/attachments/${attachmentId}`;

                fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || 'Failed to delete attachment');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Remove the attachment row with animation
                            const attachmentRow = deleteBtn.closest('.flex.items-center');
                            if (attachmentRow) {
                                attachmentRow.style.transition = 'all 0.3s ease';
                                attachmentRow.style.opacity = '0';
                                attachmentRow.style.transform = 'translateX(-20px)';
                                setTimeout(() => {
                                    attachmentRow.remove();

                                    // Update count if exists
                                    const countElement = document.querySelector(
                                        '.attachment-count');
                                    if (countElement) {
                                        const currentCount = parseInt(countElement
                                            .textContent) || 0;
                                        countElement.textContent = currentCount - 1;
                                    }

                                    // Show message if no attachments left
                                    const attachmentsContainer = document.querySelector(
                                        '.attachments-list');
                                    if (attachmentsContainer && attachmentsContainer.children
                                        .length === 0) {
                                        const parent = attachmentsContainer.parentNode;
                                        const noAttachmentsMsg = document.createElement('p');
                                        noAttachmentsMsg.className =
                                            'text-sm text-gray-500 text-center py-4';
                                        noAttachmentsMsg.textContent =
                                            'No attachments uploaded yet.';
                                        attachmentsContainer.remove();
                                        parent.appendChild(noAttachmentsMsg);
                                    }
                                }, 300);
                            }
                            // Show success message
                            const successMsg = document.createElement('div');
                            successMsg.className =
                                'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-500';
                            successMsg.textContent = 'Attachment deleted successfully!';
                            document.body.appendChild(successMsg);
                            setTimeout(() => {
                                successMsg.style.opacity = '0';
                                setTimeout(() => successMsg.remove(), 500);
                            }, 3000);
                        } else {
                            window.alert(data.message ||
                                'Failed to delete attachment. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.alert('An error occurred while deleting the attachment: ' + error
                            .message);
                    })
                    .finally(() => {
                        // Restore button
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalHtml;
                    });
            });

            // File input validation - limit to 3MB per file
            const fileInput = document.querySelector('input[name="attachments[]"]');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const maxSize = 3 * 1024 * 1024; // 3MB in bytes
                    let totalSize = 0;
                    let hasError = false;

                    for (let i = 0; i < this.files.length; i++) {
                        const file = this.files[i];
                        if (file.size > maxSize) {
                            alert(
                                `File "${file.name}" exceeds the 3MB limit. Please reduce the file size.`
                            );
                            this.value = ''; // Clear the input
                            hasError = true;
                            break;
                        }
                        totalSize += file.size;
                    }

                    if (!hasError && this.files.length > 5) {
                        alert('Maximum 5 files allowed. Please select fewer files.');
                        this.value = '';
                    }
                });
            }

            // Auto-dismiss alerts
            const alert = document.getElementById('alert-message');
            if (alert) {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            }
        });
    </script>
@endsection
