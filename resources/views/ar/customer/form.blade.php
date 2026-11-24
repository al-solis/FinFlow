@extends('dashboard')

@section('title', $customer->exists ? 'Edit Customer' : 'New Customer')

@section('content')
    @php
        $isEdit = $customer->exists;
        $formAction = $isEdit ? route('ar.customers.update', $customer->id) : route('ar.customers.store');
        $input = 'mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs';
        $label = 'block text-xs font-medium text-gray-900';
        $section = 'mb-4 mt-6 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-700';
    @endphp

    <div class="mx-auto max-w-3xl">
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
                            <h1 class="text-2xl font-bold text-white">{{ $isEdit ? 'Edit Customer' : 'New Customer' }}
                            </h1>
                            <p class="mt-1 text-sm text-blue-100">Maintain customer information used in billing and
                                collection.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('ar.cust') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                                Back
                            </a>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">
                                {{ $isEdit ? 'Update Customer' : 'Save Customer' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    {{-- General --}}
                    <h2 class="{{ $section }} !mt-0">General</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $label }}">Customer Code <span class="text-red-500">*</span></label>
                            <input type="text" name="code" maxlength="20" required
                                value="{{ old('code', $customer->code) }}" class="{{ $input }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Customer Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" maxlength="150" required
                                value="{{ old('name', $customer->name) }}" class="{{ $input }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Legal Name</label>
                            <input type="text" name="legal_name" maxlength="150"
                                value="{{ old('legal_name', $customer->legal_name) }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Category <span class="text-red-500">*</span></label>
                            <select name="customer_category_id" required class="{{ $input }}">
                                <option value="">— Select Category —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('customer_category_id', $customer->customer_category_id ?? $defaultARSettings->default_customer_category_id) == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Industry</label>
                            <input type="text" name="industry" maxlength="100"
                                value="{{ old('industry', $customer->industry) }}" class="{{ $input }}">
                        </div>
                        <div class="flex items-end gap-6 pb-2 md:col-span-2">
                            <label class="flex items-center gap-2 text-xs text-gray-700">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active))>
                                Active
                            </label>
                        </div>
                    </div>

                    {{-- Tax --}}
                    <h2 class="{{ $section }}">Tax</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $label }}">TIN</label>
                            <input type="text" name="tax_id" maxlength="30" placeholder="000-000-000-000"
                                value="{{ old('tax_id', $customer->tax_id) }}" class="{{ $input }}">
                            <p class="mt-1 text-[11px] text-gray-400">Printed on the invoice. Required by BIR for
                                business customers.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Branch Code</label>
                            <input type="text" name="tax_branch_code" maxlength="10"
                                value="{{ old('tax_branch_code', $customer->tax_branch_code) }}"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">SEC / DTI Registration No.</label>
                            <input type="text" name="registration_no" maxlength="100"
                                value="{{ old('registration_no', $customer->registration_no) }}"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">VAT Classification <span
                                    class="text-red-500">*</span></label>
                            <select name="vat_classification" required class="{{ $input }}">
                                @foreach (['vatable' => 'VATable', 'zero_rated' => 'Zero-Rated', 'exempt' => 'VAT-Exempt', 'non_vat' => 'Non-VAT'] as $value => $text)
                                    <option value="{{ $value }}" @selected(old('vat_classification', $customer->vat_classification) === $value)>
                                        {{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Default Withholding Tax (CWT)</label>
                            <select name="default_cwt_tax_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($cwtTaxes as $tax)
                                    <option value="{{ $tax->id }}" @selected(old('default_cwt_tax_id', $customer->default_cwt_tax_id) == $tax->id)>
                                        {{ $tax->code }} — {{ $tax->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- <div>
                            <label class="{{ $label }}">Tax Group</label>
                            <select name="tax_group_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($taxGroups as $group)
                                    <option value="{{ $group->id }}" @selected(old('tax_group_id', $customer->tax_group_id) == $group->id)>
                                        {{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div> --}}
                        <div class="md:col-span-3">
                            <label class="flex items-center gap-2 text-xs text-gray-700">
                                <input type="checkbox" name="is_withholding_agent" value="1"
                                    @checked(old('is_withholding_agent', $customer->is_withholding_agent))>
                                Withholding agent — this customer withholds tax from payments and issues BIR Form 2307
                            </label>
                        </div>
                    </div>

                    {{-- Address --}}
                    <h2 class="{{ $section }}">Billing Address</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        @foreach ([['address1', 'Address Line 1', 255, 'md:col-span-3'], ['address2', 'Address Line 2', 255, 'md:col-span-3'], ['city', 'City / Municipality', 100, ''], ['province', 'Province', 100, ''], ['zip_code', 'ZIP Code', 20, ''], ['country', 'Country', 100, '']] as [$name, $text, $max, $span])
                            <div class="{{ $span }}">
                                <label class="{{ $label }}">{{ $text }}</label>
                                <input type="text" name="{{ $name }}" maxlength="{{ $max }}"
                                    value="{{ old($name, $customer->{$name}) }}" class="{{ $input }}">
                            </div>
                        @endforeach
                    </div>

                    {{-- Contact --}}
                    <h2 class="{{ $section }}">Contact</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        @foreach ([['contact_person', 'Contact Person', 100, 'text'], ['position', 'Position', 100, 'text'], ['email', 'Email', 100, 'email'], ['phone', 'Phone', 20, 'text'], ['mobile', 'Mobile', 20, 'text'], ['website', 'Website', 100, 'text']] as [$name, $text, $max, $type])
                            <div>
                                <label class="{{ $label }}">{{ $text }}</label>
                                <input type="{{ $type }}" name="{{ $name }}"
                                    maxlength="{{ $max }}" value="{{ old($name, $customer->{$name}) }}"
                                    class="{{ $input }}">
                            </div>
                        @endforeach
                    </div>

                    {{-- Financial --}}
                    <h2 class="{{ $section }}">Financial</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $label }}">Currency</label>
                            <select name="currency_id" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" @selected(old('currency_id', $customer->currency_id) == $currency->id)>
                                        {{ $currency->code }} — {{ $currency->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Payment Term</label>
                            <select name="payment_term_id" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}" @selected(old('payment_term_id', $customer->payment_term_id ?? $defaultARSettings->default_payment_term_id) == $term->id)>
                                        {{ $term->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Preferred Payment Method</label>
                            <select name="payment_method_id" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($paymentMethods as $method)
                                    <option value="{{ $method->id }}" @selected(old('payment_method_id', $customer->payment_method_id) == $method->id)>
                                        {{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">AR Main Account</label>
                            <select name="ar_account_id" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($mainAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('ar_account_id', $customer->ar_account_id) == $account->id)>
                                        {{ $account->code }} — {{ $account->description }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Default AR Control Account (posting) <span
                                    class="text-gray-400">—
                                    used when posting invoices and collections</span></label>
                            <select name="default_ar_chart_of_account_id" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($arAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('default_ar_chart_of_account_id', $customer->default_ar_chart_of_account_id) == $account->id)>
                                        {{ $account->account_code }} — {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Default Sales Discount Account <span
                                    class="text-gray-400">—
                                    contra-revenue account used when this customer's discounts are posted to the
                                    GL</span></label>
                            <select name="default_sales_discount_account_id" class="{{ $input }}">
                                <option value="">— Use AR Settings default —</option>
                                @foreach ($discountAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('default_sales_discount_account_id', $customer->default_sales_discount_account_id) == $account->id)>
                                        {{ $account->account_code }} — {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">
                                Only used when <em>Record Sales Discount in GL</em> is enabled in AR Settings.
                                Leave blank to use the organization default.
                            </p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Credit Limit</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-500">₱</span>
                                <input type="number" step="0.01" min="0" name="credit_limit"
                                    value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}"
                                    class="{{ $input }} pl-7 text-right">
                            </div>
                            <p class="mt-1 text-[11px] text-gray-400">0 means no limit.</p>
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center gap-2 text-xs text-gray-700">
                                <input type="checkbox" name="credit_hold" value="1" @checked(old('credit_hold', $customer->credit_hold))>
                                Credit hold (block new invoices)
                            </label>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="{{ $label }}">Contact Notes / Remarks</label>
                        <textarea name="remarks" rows="3" class="{{ $input }}">{{ old('remarks', $customer->remarks) }}</textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
