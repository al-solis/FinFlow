@extends('dashboard')
@section('title', 'Organization Profile')
@section('content')
    <div class="max-w-7xl mx-auto">

        @if (session('success'))
            <div id="success-alert"
                class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800 shadow-sm transition-all duration-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-4 w-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ session('success') }}
                    </div>
                    <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                        class="text-green-600 hover:text-green-800 transition-colors duration-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div id="error-alert"
                class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 shadow-sm transition-all duration-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-4 w-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ session('error') }}
                    </div>
                    <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                        class="text-red-600 hover:text-red-800 transition-colors duration-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.org.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="bg-white rounded-xl shadow mt-2">
                <div class="flex items-center justify-between px-6 py-5 border-b">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            Organization Profile
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Configure your company information and regional settings.
                        </p>
                    </div>

                    <div>
                        @if ($organization->logo)
                            <img src="{{ asset('storage/' . $organization->logo) }}"
                                class="w-20 h-20 object-contain rounded-lg border">
                        @else
                            <div class="w-20 h-20 rounded-lg border flex items-center justify-center bg-gray-100">
                                <svg class="w-10 h-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M3 7l9-4 9 4v10l-9 4-9-4V7z" />
                                </svg>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="p-6 space-y-10">

                    <!-- ================================================= -->
                    <!-- BASIC INFORMATION -->
                    <!-- ================================================= -->
                    <input type="hidden" name="id" id="id" value="{{ old('id', $organization->id) }}">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-5">
                            Basic Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                            <!-- Organization Code -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Organization Code
                                </label>
                                <input type="text" name="organization_code" readonly
                                    value="{{ old('organization_code', $organization->organization_code) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Organization Name -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Organization Name
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name', $organization->name) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Short Name -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Short Name
                                </label>
                                <input type="text" name="short_name"
                                    value="{{ old('short_name', $organization->short_name) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Legal Name -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Legal Name
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="legal_name"
                                    value="{{ old('legal_name', $organization->legal_name) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Business Type -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Business Type
                                </label>
                                <select name="business_type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select --</option>
                                    <option value="Corporation"
                                        {{ old('business_type', $organization->business_type) == 'Corporation' ? 'selected' : '' }}>
                                        Corporation
                                    </option>
                                    <option value="Partnership"
                                        {{ old('business_type', $organization->business_type) == 'Partnership' ? 'selected' : '' }}>
                                        Partnership
                                    </option>
                                    <option value="Sole Proprietorship"
                                        {{ old('business_type', $organization->business_type) == 'Sole Proprietorship' ? 'selected' : '' }}>
                                        Sole Proprietorship
                                    </option>
                                    <option value="Government"
                                        {{ old('business_type', $organization->business_type) == 'Government' ? 'selected' : '' }}>
                                        Government
                                    </option>
                                    <option value="Non-Profit"
                                        {{ old('business_type', $organization->business_type) == 'Non-Profit' ? 'selected' : '' }}>
                                        Non-Profit
                                    </option>
                                </select>

                            </div>

                            <!-- Industry -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Industry
                                </label>
                                <input type="text" name="industry"
                                    value="{{ old('industry', $organization->industry) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Status
                                </label>
                                <select name="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1"
                                        {{ old('status', $organization->status) == 1 ? 'selected' : '' }}>
                                        Active
                                    </option>
                                    <option value="0"
                                        {{ old('status', $organization->status) == 0 ? 'selected' : '' }}>
                                        Inactive
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- BUSINESS REGISTRATION -->
                    <!-- ================================================= -->

                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-5">
                            Business Registration
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                            <!-- TIN -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Tax Identification No. (TIN)
                                </label>
                                <input type="text" name="tax_id" value="{{ old('tax_id', $organization->tax_id) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- SEC / DTI -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    SEC / DTI Registration No.
                                </label>
                                <input type="text" name="registration_no"
                                    value="{{ old('registration_no', $organization->registration_no) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Branch Code -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Tax Branch Code
                                </label>
                                <input type="text" name="tax_branch_code"
                                    value="{{ old('tax_branch_code', $organization->tax_branch_code) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- BIR RDO -->
                            <div>

                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    BIR RDO Code
                                </label>
                                <input type="text" name="bir_rdo_code"
                                    value="{{ old('bir_rdo_code', $organization->bir_rdo_code) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- ADDRESS -->
                    <!-- ================================================= -->

                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-5">
                            Address Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Complete Address
                                </label>
                                <textarea name="address" rows="3"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('address', $organization->address) }}</textarea>
                            </div>

                            <!-- City -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    City / Municipality
                                </label>
                                <input type="text" name="city" value="{{ old('city', $organization->city) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Province -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Province / State
                                </label>
                                <input type="text" name="province"
                                    value="{{ old('province', $organization->province) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Country -->
                            <div>

                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Country
                                </label>
                                <input type="text" name="country"
                                    value="{{ old('country', $organization->country) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- ZIP -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    ZIP / Postal Code
                                </label>
                                <input type="text" name="zip_code"
                                    value="{{ old('zip_code', $organization->zip_code) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- CONTACT INFORMATION -->
                    <!-- ================================================= -->

                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-5">
                            Contact Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <!-- Contact Person -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Contact Person
                                </label>
                                <input type="text" name="contact_person"
                                    value="{{ old('contact_person', $organization->contact_person) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Telephone No.
                                </label>
                                <input type="text" name="phone" value="{{ old('phone', $organization->phone) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Mobile -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Mobile No.
                                </label>

                                <input type="text" name="mobile" value="{{ old('mobile', $organization->mobile) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Email Address
                                </label>
                                <input type="email" name="email" value="{{ old('email', $organization->email) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Website -->

                            <div class="md:col-span-2">
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Website
                                </label>
                                <input type="url" name="website"
                                    value="{{ old('website', $organization->website) }}"
                                    placeholder="https://example.com"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                        </div>

                    </div>

                    <!-- ================================================= -->
                    <!-- REGIONAL SETTINGS -->
                    <!-- ================================================= -->

                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-5">
                            Regional Settings
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <!-- Currency -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Base Currency
                                </label>
                                <select name="currency_id" id="currency_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ old('currency_id', $organization->currency_id) == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->code }} - {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Timezone -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Time Zone
                                </label>
                                <select name="timezone"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="Asia/Manila"
                                        {{ old('timezone', $organization->timezone) == 'Asia/Manila' ? 'selected' : '' }}>
                                        Asia/Manila
                                    </option>
                                    <option value="UTC"
                                        {{ old('timezone', $organization->timezone) == 'UTC' ? 'selected' : '' }}>
                                        UTC
                                    </option>
                                </select>

                            </div>

                            <!-- Language -->
                            <div>

                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Language
                                </label>

                                <select name="language"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">

                                    <option value="English"
                                        {{ old('language', $organization->language) == 'English' ? 'selected' : '' }}>
                                        English
                                    </option>

                                </select>

                            </div>

                            <!-- Date Format -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Date Format
                                </label>

                                <select name="date_format"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">

                                    <option value="Y-m-d"
                                        {{ old('date_format', $organization->date_format) == 'Y-m-d' ? 'selected' : '' }}>
                                        YYYY-MM-DD
                                    </option>
                                    <option value="m/d/Y"
                                        {{ old('date_format', $organization->date_format) == 'm/d/Y' ? 'selected' : '' }}>
                                        MM/DD/YYYY
                                    </option>
                                    <option value="d/m/Y"
                                        {{ old('date_format', $organization->date_format) == 'd/m/Y' ? 'selected' : '' }}>
                                        DD/MM/YYYY
                                    </option>
                                </select>
                            </div>

                            <!-- Number Format -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Number Format
                                </label>
                                <select name="number_format"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1,234.56"
                                        {{ old('number_format', $organization->number_format) == '1,234.56' ? 'selected' : '' }}>
                                        1,234.56</option>
                                    <option value="1.234,56"
                                        {{ old('number_format', $organization->number_format) == '1.234,56' ? 'selected' : '' }}>
                                        1.234,56</option>
                                </select>
                            </div>

                            <!-- Decimal Places -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Decimal Places
                                </label>
                                <select name="decimal_places"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="2"
                                        {{ old('decimal_places', $organization->decimal_places) == 2 ? 'selected' : '' }}>
                                        2
                                    </option>
                                    <option value="3"
                                        {{ old('decimal_places', $organization->decimal_places) == 3 ? 'selected' : '' }}>
                                        3
                                    </option>
                                    <option value="4"
                                        {{ old('decimal_places', $organization->decimal_places) == 4 ? 'selected' : '' }}>
                                        4
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- ACCOUNTING SETTINGS -->
                    <!-- ================================================= -->

                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-5">
                            Accounting Settings
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <!-- Accounting Method -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Accounting Method
                                </label>
                                <select name="accounting_method"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="Accrual"
                                        {{ old('accounting_method', $organization->accounting_method) == 'Accrual' ? 'selected' : '' }}>
                                        Accrual
                                    </option>
                                    <option value="Cash"
                                        {{ old('accounting_method', $organization->accounting_method) == 'Cash' ? 'selected' : '' }}>
                                        Cash
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Accrual accounting is recommended for most organizations.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- BRANDING -->
                    <!-- ================================================= -->

                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-5">
                            Branding
                        </h2>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Upload Logo -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Organization Logo
                                </label>
                                <input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg"
                                    class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                <p class="mt-2 text-xs text-gray-500">
                                    Recommended size: 300 × 300 pixels.
                                </p>
                            </div>
                            <!-- Logo Preview -->
                            <div>
                                <label class="block text-xs mb-2 font-medium text-gray-900 dark:text-white">
                                    Current Logo
                                </label>
                                @if ($organization->logo)
                                    <div class="border rounded-lg p-5 bg-gray-50 flex items-center justify-center">
                                        <img id="logo-preview" src="{{ asset('storage/' . $organization->logo) }}"
                                            class="max-h-40 object-contain mx-auto">
                                    </div>
                                @else
                                    <div class="border rounded-lg p-10 bg-gray-50 text-center text-gray-400">
                                        No logo uploaded.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- DESCRIPTION -->
                    <!-- ================================================= -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-5">
                            Description
                        </h2>
                        <textarea name="description" rows="5"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('description', $organization->description) }}</textarea>
                        <p class="mt-2 text-xs text-gray-500">
                            Briefly describe the organization, its business activities, or other relevant information.
                        </p>
                    </div>
                </div>

                <!-- ================================================= -->
                <!-- ACTION BUTTONS -->
                <!-- ================================================= -->

                <div class="flex items-center justify-between px-6 py-4 border-t bg-gray-50 rounded-b-xl">
                    <div class="text-sm text-gray-500">
                        Last Updated:
                        {{ optional($organization->updated_at)->format('F d, Y h:i A') }}
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('mainDashboard') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                            Save Organization
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // document.querySelectorAll('[id$="-alert"]').forEach(alert => {
        //     setTimeout(() => {
        //         alert.style.transition = 'opacity 0.5s ease';
        //         alert.style.opacity = '0';
        //         setTimeout(() => {
        //             alert.style.display = 'none';
        //         }, 500);
        //     }, 5000);
        // });

        document.addEventListener('DOMContentLoaded', function() {

            const input = document.querySelector('input[name="logo"]');
            if (!input)
                return;

            input.addEventListener('change', function(e) {
                const file = e.target.files[0];

                if (!file)
                    return;

                const reader = new FileReader();

                reader.onload = function(event) {
                    let img = document.querySelector('#logo-preview');

                    if (!img) {
                        const container = input.closest('.grid').querySelector('.border');
                        container.innerHTML = '';
                        img = document.createElement('img');
                        img.id = 'logo-preview';
                        img.className = 'max-h-40 object-contain mx-auto';
                        container.appendChild(img);
                    }
                    img.src = event.target.result;
                };
                reader.readAsDataURL(file);
            });
        });
    </script>

@endsection
