@extends('dashboard')
@section('title', 'Vendor Master')
@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div id="alert-message"
                class="mt-2 mb-5 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div id="alert-message" class="mt-2 mb-5 rounded-lg border border-red-300 bg-red-50 p-4">
                <div class="font-semibold text-red-700">
                    Please correct the following errors:
                </div>

                <ul class="mt-2 list-disc list-inside text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('ap.vendors.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="mt-2 bg-white rounded-xl shadow-sm border border-gray-200">
                <!-- Header -->

                <div class="flex items-center justify-between border-b px-6 py-5">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            Vendor Master
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Maintain supplier information used throughout the Accounts Payable module.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('ap.vendors') }}"
                            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100">
                            Back
                        </a>

                        <button type="submit"
                            class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                            Save Vendor
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="vendorTabs"
                        data-tabs-toggle="#vendorTabContent" role="tablist">
                        <li class="me-2" role="presentation">
                            <button class="inline-block rounded-t-lg border-b-2 p-4" id="general-tab"
                                data-tabs-target="#general" type="button" role="tab">
                                General
                            </button>
                        </li>

                        <li class="me-2" role="presentation">
                            <button class="inline-block rounded-t-lg border-b-2 p-4" id="tax-tab" data-tabs-target="#tax"
                                type="button" role="tab">
                                Tax
                            </button>
                        </li>
                        <li class="me-2" role="presentation">
                            <button class="inline-block rounded-t-lg border-b-2 p-4" id="address-tab"
                                data-tabs-target="#address" type="button" role="tab">
                                Address
                            </button>
                        </li>

                        <li class="me-2" role="presentation">
                            <button class="inline-block rounded-t-lg border-b-2 p-4" id="contact-tab"
                                data-tabs-target="#contact" type="button" role="tab">
                                Contact
                            </button>
                        </li>

                        <li class="me-2" role="presentation">

                            <button class="inline-block rounded-t-lg border-b-2 p-4" id="financial-tab"
                                data-tabs-target="#financial" type="button" role="tab">
                                Financial
                            </button>
                        </li>

                        <li class="me-2" role="presentation">
                            <button class="inline-block rounded-t-lg border-b-2 p-4" id="bank-tab" data-tabs-target="#bank"
                                type="button" role="tab">
                                Banking
                            </button>
                        </li>

                        <li role="presentation">
                            <button class="inline-block rounded-t-lg border-b-2 p-4" id="more-tab" data-tabs-target="#more"
                                type="button" role="tab">
                                More
                            </button>
                        </li>
                    </ul>
                </div>

                <div id="vendorTabContent">
                    <!-- ================================================= -->
                    <!-- GENERAL TAB -->
                    <!-- ================================================= -->

                    <div id="general" role="tabpanel" class="space-y-6 p-6">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Vendor Code -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Vendor Code
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="vendor_code" name="vendor_code" value="{{ old('vendor_code') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Vendor Name -->
                            <div class="lg:col-span-2">
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Vendor Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input type="text" id="vendor_name" name="vendor_name" value="{{ old('vendor_name') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                            <!-- Status -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
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
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Legal Name
                                </label>
                                <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                            <!-- Vendor Category -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Vendor Category
                                    <span class="text-red-500">*</span>
                                </label>
                                <select name="vendor_category_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Category --</option>
                                    @foreach ($vendorCategories as $category)
                                        <option value="{{ $category->id }}" @selected(old('vendor_category_id') == $category->id)>
                                            {{ $category->code }} - {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Vendor Type -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Vendor Type
                                </label>
                                <select name="vendor_type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="Company" @selected(old('vendor_type') == 'Company')>
                                        Company
                                    </option>
                                    <option value="Individual" @selected(old('vendor_type') == 'Individual')>
                                        Individual
                                    </option>
                                    <option value="Government" @selected(old('vendor_type') == 'Government')>
                                        Government
                                    </option>
                                </select>
                            </div>

                            <!-- Industry -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Industry
                                </label>
                                <input type="text" name="industry" value="{{ old('industry') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                            <!-- Currency -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Currency
                                </label>
                                <select name="currency_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Currency --</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}" @selected(old('currency_id') == $currency->id)>
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
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Default Payment Term
                                </label>
                                <select name="payment_term_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Payment Term --</option>
                                    @foreach ($paymentTerms as $term)
                                        <option value="{{ $term->id }}" @selected(old('payment_term_id') == $term->id)>
                                            {{ $term->code }} - {{ $term->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Preferred Language -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Preferred Language
                                </label>
                                <select name="language"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="English" @selected(old('language') == 'English')>
                                        English
                                    </option>
                                    <option value="Filipino" @selected(old('language') == 'Filipino')>
                                        Filipino
                                    </option>
                                </select>
                            </div>
                        </div>
                        <!-- Vendor Notes -->
                        <div>
                            <label class="mb-2 block text-xs font-medium text-gray-700">
                                Vendor Notes
                            </label>
                            <textarea name="remarks" rows="4"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('remarks') }}</textarea>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- TAX TAB -->
                    <!-- ================================================= -->
                    <div class="hidden space-y-6 p-6" id="tax" role="tabpanel">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                            <!-- TIN -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Tax Identification No. (TIN)
                                </label>
                                <input type="text" name="tax_id" value="{{ old('tax_id') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                            <!-- Branch Code -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Tax Branch Code
                                </label>
                                <input type="text" name="tax_branch_code" value="{{ old('tax_branch_code') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Registration Number -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    SEC / DTI Registration No.
                                </label>
                                <input type="text" name="registration_no" value="{{ old('registration_no') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Tax Group -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Tax Group
                                </label>
                                <select name="tax_group_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Tax Group --</option>
                                    @foreach ($taxGroups as $taxGroup)
                                        <option value="{{ $taxGroup->id }}" @selected(old('tax_group_id') == $taxGroup->id)>
                                            {{ $taxGroup->code }} - {{ $taxGroup->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- VAT Registered -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    VAT Registration
                                </label>
                                <select name="vat_registered"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1" @selected(old('vat_registered', 1) == 1)>
                                        VAT Registered
                                    </option>

                                    <option value="0" @selected(old('vat_registered') === '0')>
                                        Non-VAT
                                    </option>
                                </select>

                            </div>

                            <!-- Withholding Tax -->

                            <div>

                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Subject to Withholding Tax
                                </label>

                                <select name="subject_to_withholding"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1" @selected(old('subject_to_withholding') == '1')>
                                        Yes
                                    </option>
                                    <option value="0" @selected(old('subject_to_withholding', '0') == '0')>
                                        No
                                    </option>
                                </select>
                            </div>

                            <!-- Withholding Tax -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Default Withholding Tax
                                </label>
                                <select name="withholding_tax_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Withholding Tax --</option>
                                    @foreach ($withholdingTaxes as $tax)
                                        <option value="{{ $tax->id }}" @selected(old('withholding_tax_id') == $tax->id)>
                                            {{ $tax->code }} - {{ $tax->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- VAT Tax -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Default VAT Tax
                                </label>
                                <select name="vat_tax_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select VAT Tax --</option>
                                    @foreach ($vatTaxes as $tax)
                                        <option value="{{ $tax->id }}" @selected(old('vat_tax_id') == $tax->id)>
                                            {{ $tax->code }} - {{ $tax->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Tax Notes -->
                        <div>
                            <label class="mb-2 block text-xs font-medium text-gray-700">
                                Tax Remarks
                            </label>
                            <textarea name="tax_remarks" rows="4"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('tax_remarks') }}</textarea>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- ADDRESS TAB -->
                    <!-- ================================================= -->
                    <div id="address" role="tabpanel" class="hidden space-y-6 p-6">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <!-- Address Line 1 -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Address Line 1
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="address1" value="{{ old('address1') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Address Line 2 -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Address Line 2
                                </label>
                                <input type="text" name="address2" value="{{ old('address2') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- City -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    City / Municipality
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="city" value="{{ old('city') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Province -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Province / State
                                </label>
                                <input type="text" name="province" value="{{ old('province') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Country -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Country
                                    <span class="text-red-500">*</span>
                                </label>
                                <select name="country"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Country --</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->name }}" @selected(old('country') == $country->name)>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- ZIP Code -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    ZIP / Postal Code
                                </label>
                                <input type="text" name="zip_code" value="{{ old('zip_code') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- CONTACT TAB -->
                    <!-- ================================================= -->
                    <div id="contact" role="tabpanel" class="hidden space-y-6 p-6">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                            <!-- Contact Person -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Contact Person
                                </label>
                                <input type="text" name="contact_person" value="{{ old('contact_person') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Position -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Position
                                </label>
                                <input type="text" name="position" value="{{ old('position') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Email Address
                                </label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Telephone No.
                                </label>
                                <input type="text" name="phone" value="{{ old('phone') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Mobile -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Mobile No.
                                </label>
                                <input type="text" name="mobile" value="{{ old('mobile') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Website -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Website
                                </label>
                                <input type="url" name="website" value="{{ old('website') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-medium text-gray-700">
                                Contact Notes
                            </label>
                            <textarea name="contact_notes" rows="4"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('contact_notes') }}</textarea>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- FINANCIAL TAB -->
                    <!-- ================================================= -->

                    <div id="financial" role="tabpanel" class="hidden space-y-6 p-6">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Currency -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Currency
                                </label>
                                <select name="currency_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Currency --</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}" @selected(old('currency_id') == $currency->id)>
                                            {{ $currency->code }} - {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Payment Term -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Payment Term
                                </label>
                                <select name="payment_term_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Payment Term --</option>
                                    @foreach ($paymentTerms as $term)
                                        <option value="{{ $term->id }}" @selected(old('payment_term_id') == $term->id)>
                                            {{ $term->code }} - {{ $term->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Payment Method
                                </label>
                                <select name="payment_method_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select Payment Method --</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->id }}" @selected(old('payment_method_id') == $method->id)>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- AP Control Account -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    AP Control Account
                                </label>
                                <select name="ap_account_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="">-- Select GL Account --</option>
                                    @foreach ($apAccounts as $account)
                                        <option value="{{ $account->id }}" @selected(old('ap_account_id') == $account->id)>
                                            {{ $account->code }} - {{ $account->description }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Credit Limit -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Credit Limit
                                </label>
                                <input type="number" step="0.01" min="0" name="credit_limit"
                                    value="{{ old('credit_limit', 0) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Default Discount -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Default Discount (%)
                                </label>
                                <input type="number" step="0.01" min="0" name="discount_percent"
                                    value="{{ old('discount_percent', 0) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Preferred Payment Day -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Preferred Payment Day
                                </label>
                                <input type="number" min="1" max="31" name="preferred_payment_day"
                                    value="{{ old('preferred_payment_day') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Payment Priority -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Payment Priority
                                </label>
                                <select name="payment_priority"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="Low">Low</option>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- BANK ACCOUNTS TAB -->
                    <!-- ================================================= -->
                    <div id="bank" role="tabpanel" class="hidden space-y-6 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    Vendor Bank Accounts
                                </h3>
                                <p class="text-sm text-gray-500">
                                    A vendor may have multiple bank accounts.
                                </p>
                            </div>

                            <button type="button" id="btnAddBank"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                                + Add Bank Account
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Bank</th>
                                        <th class="px-3 py-2 text-left">Branch</th>
                                        <th class="px-3 py-2 text-left">Account Name</th>
                                        <th class="px-3 py-2 text-left">Account Number</th>
                                        <th class="px-3 py-2 text-left">SWIFT</th>
                                        <th class="px-3 py-2 text-center">Primary</th>
                                        <th class="px-3 py-2 text-center" width="120">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody id="bankTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- MORE TAB -->
                    <!-- ================================================= -->
                    <div id="more" role="tabpanel" class="hidden space-y-6 p-6">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Requires Purchase Order -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Requires Purchase Order
                                </label>
                                <select name="requires_po"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1" @selected(old('requires_po', 1) == 1)>
                                        Yes
                                    </option>
                                    <option value="0" @selected(old('requires_po') === '0')>
                                        No
                                    </option>
                                </select>
                            </div>

                            <!-- Lead Time -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Lead Time (Days)
                                </label>
                                <input type="number" min="0" name="lead_time" value="{{ old('lead_time') }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Status
                                </label>
                                <select name="is_active"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="1" @selected(old('is_active', 1) == 1)>
                                        Active
                                    </option>
                                    <option value="0" @selected(old('is_active') === '0')>
                                        Inactive
                                    </option>
                                </select>
                            </div>

                            <!-- Preferred Vendor -->
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-700">
                                    Preferred Vendor
                                </label>
                                <select name="preferred_vendor"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                    <option value="0">No</option>
                                    <option value="1" @selected(old('preferred_vendor') == '1')>
                                        Yes
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Remarks -->

                        <div>
                            <label class="mb-2 block text-xs font-medium text-gray-700">
                                Internal Remarks
                            </label>
                            <textarea name="remarks" rows="5"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">{{ old('remarks') }}</textarea>
                        </div>

                        <!-- Attachments -->
                        <div>
                            <h3 class="mb-3 text-lg font-semibold text-gray-800">
                                Attachments
                            </h3>

                            <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center">

                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16V8a5 5 0 0110 0v8a3 3 0 11-6 0V9" />
                                </svg>

                                <p class="text-sm text-gray-500">
                                    Upload supporting documents.
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    BIR 2303, SEC/DTI Registration, Business Permit,
                                    Supplier Accreditation, Contracts, etc.
                                </p>
                                <input type="file" name="attachments[]" multiple
                                    class="mt-5 block w-full rounded-lg border border-gray-300 text-sm">
                            </div>

                        </div>

                        <!-- Audit -->

                        <div>

                            <h3 class="mb-3 text-lg font-semibold text-gray-800">
                                Audit Information
                            </h3>

                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label class="mb-2 block text-xs font-medium text-gray-500">
                                        Created By
                                    </label>
                                    <input type="text" readonly
                                        value="{{ isset($vendor) ? optional($vendor->creator)->name : '' }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium text-gray-500">
                                        Created At
                                    </label>
                                    <input type="text" readonly
                                        value="{{ isset($vendor) && $vendor->created_at ? $vendor->created_at->format(settings()->date_format) : '' }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium text-gray-500">
                                        Updated By
                                    </label>
                                    <input type="text" readonly
                                        value="{{ isset($vendor) ? optional($vendor->updater)->name : '' }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-medium text-gray-500">
                                        Updated At
                                    </label>
                                    <input type="text" readonly
                                        value="{{ isset($vendor) && $vendor->updated_at ? $vendor->updated_at->format(settings()->date_format) : '' }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-600 focus:border-gray-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.getElementById('alert-message');

                if (alert) {
                    setTimeout(() => {
                        alert.style.transition = 'opacity 0.5s ease';
                        alert.style.opacity = '0';

                        setTimeout(() => {
                            alert.remove();
                        }, 500);
                    }, 3000);
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                const tbody = document.getElementById('bankTableBody');
                const btnAdd = document.getElementById('btnAddBank');

                let rowIndex = 0;

                btnAdd.addEventListener('click', function() {
                    addBankRow();
                });

                function addBankRow(data = {}) {
                    const tr = document.createElement('tr');
                    tr.className = "border-t";
                    tr.innerHTML = `
            <td class="p-2">
                <input
                    type="text"
                    name="banks[${rowIndex}][bank_name]"
                    value="${data.bank_name ?? ''}"
                    class="w-full rounded-lg border border-gray-300 p-2 text-sm">
            </td>

            <td class="p-2">
                <input
                    type="text"
                    name="banks[${rowIndex}][branch]"
                    value="${data.branch ?? ''}"
                    class="w-full rounded-lg border border-gray-300 p-2 text-sm">
            </td>

            <td class="p-2">
                <input
                    type="text"
                    name="banks[${rowIndex}][account_name]"
                    value="${data.account_name ?? ''}"
                    class="w-full rounded-lg border border-gray-300 p-2 text-sm">
            </td>

            <td class="p-2">
                <input
                    type="text"
                    name="banks[${rowIndex}][account_number]"
                    value="${data.account_number ?? ''}"
                    class="w-full rounded-lg border border-gray-300 p-2 text-sm">
            </td>

            <td class="p-2">
                <input
                    type="text"
                    name="banks[${rowIndex}][swift_code]"
                    value="${data.swift_code ?? ''}"
                    class="w-full rounded-lg border border-gray-300 p-2 text-sm">
            </td>

            <td class="text-center">
                <input
                    type="radio"
                    name="primary_bank"
                    value="${rowIndex}"
                    ${data.is_primary ? 'checked' : ''}>
            </td>

            <td class="text-center">
                <button
                    type="button"
                    class="remove-row rounded bg-red-600 px-3 py-2 text-xs text-white hover:bg-red-700">
                    Remove
                </button>
            </td>
        `;

                    tbody.appendChild(tr);
                    rowIndex++;
                }
                tbody.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-row')) {
                        e.target.closest('tr').remove();
                    }
                });
            });
        </script>
    @endpush
@endsection
