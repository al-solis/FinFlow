@extends('dashboard')

@section('title', 'AR Settings')

@section('content')
    @php
        $input = 'mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs';
        $label = 'block text-xs font-medium text-gray-900';
        $section = 'mb-4 mt-6 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-700';
    @endphp

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

        @if (session('success'))
            <div class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('ar.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">AR Settings</h1>
                            <p class="mt-1 text-sm text-blue-100">
                                Default accounts and values used when a customer, item, or tax
                                master doesn't specify its own.
                            </p>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">
                            Save Settings
                        </button>
                    </div>
                </div>

                <div class="px-8 py-6">
                    {{-- ============================================================ --}}
                    {{-- Default accounts                                              --}}
                    {{-- ============================================================ --}}
                    <h2 class="{{ $section }} !mt-0">Default Accounts</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="{{ $label }}">Accounts Receivable (control)</label>
                            <select name="default_ar_account_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($arAccounts as $a)
                                    <option value="{{ $a->id }}" @selected((string) old('default_ar_account_id', $settings->default_ar_account_id) === (string) $a->id)>
                                        {{ $a->account_code }} — {{ $a->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">
                                Fallback when a customer has no AR account of its own.
                            </p>
                        </div>

                        <div>
                            <label class="{{ $label }}">Default Revenue</label>
                            <select name="default_revenue_account_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($revenueAccounts as $a)
                                    <option value="{{ $a->id }}" @selected((string) old('default_revenue_account_id', $settings->default_revenue_account_id) === (string) $a->id)>
                                        {{ $a->account_code }} — {{ $a->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">
                                Fallback when a sellable item has no revenue account.
                            </p>
                        </div>

                        <div>
                            <label class="{{ $label }}">Sales Discount (contra-revenue)</label>
                            <select name="default_sales_discount_account_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($discountAccounts as $a)
                                    <option value="{{ $a->id }}" @selected((string) old('default_sales_discount_account_id', $settings->default_sales_discount_account_id) === (string) $a->id)>
                                        {{ $a->account_code }} — {{ $a->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">
                                Debit when a header-level discount is applied. Line discounts are
                                already netted into the revenue line.
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-start gap-3 text-xs text-gray-700">
                                <input type="checkbox" name="record_sales_discount_in_gl" value="1"
                                    @checked(old('record_sales_discount_in_gl', $settings->record_sales_discount_in_gl)) class="mt-0.5">
                                <span>
                                    <span class="font-medium">Record Sales Discount in GL</span>
                                    <span class="block text-[11px] text-gray-500 mt-0.5">
                                        When enabled, invoice discounts post as a separate <em>Dr Sales Discount</em> line,
                                        and revenue is credited at the gross (before-discount) amount. When disabled
                                        (default), discounts stay netted into the revenue credit, which is the simpler
                                        method and matches older invoices. Both methods produce identical net income
                                        and tax.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div>
                            <label class="{{ $label }}">Sales Returns (contra-revenue)</label>
                            <select name="default_sales_return_account_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($returnAccounts as $a)
                                    <option value="{{ $a->id }}" @selected((string) old('default_sales_return_account_id', $settings->default_sales_return_account_id) === (string) $a->id)>
                                        {{ $a->account_code }} — {{ $a->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">
                                Debit when a credit memo is issued (phase 3).
                            </p>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- Defaults for new records                                      --}}
                    {{-- ============================================================ --}}
                    <h2 class="{{ $section }}">Defaults for New Customers</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="{{ $label }}">Payment Term</label>
                            <select name="default_payment_term_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($terms as $t)
                                    <option value="{{ $t->id }}" @selected((string) old('default_payment_term_id', $settings->default_payment_term_id) === (string) $t->id)>
                                        {{ $t->name }} ({{ $t->days }} days)
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">
                                Pre-filled on new customer records; still editable per customer.
                            </p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Customer Category</label>
                            <select name="default_customer_category_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($customerCategories as $c)
                                    <option value="{{ $c->id }}" @selected((string) old('default_customer_category_id', $settings->default_customer_category_id) === (string) $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">
                                Pre-filled on new customer records; still editable per customer.
                            </p>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- Explanation                                                    --}}
                    {{-- ============================================================ --}}
                    <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-3 text-[11px] text-blue-800">
                        <strong>How these are used.</strong> Every field here is a <em>fallback</em>.
                        If the customer has its own AR account, or the item has its own revenue
                        account, that value wins. These settings only apply when nothing more
                        specific is set on the record. Tax accounts (Output VAT, Input VAT, CWT)
                        and BIR numbering are configured on the Tax Master and Invoice Series
                        pages respectively — not here. The <strong>Record Sales Discount in GL</strong>
                        toggle controls whether invoice discounts are posted as their own line
                        or netted into revenue — changing it affects all future postings, not
                        historical ones.
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
