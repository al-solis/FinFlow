@extends('dashboard')

@section('title', $item->exists ? 'Edit Item' : 'New Item')

@section('content')
    @php
        $isEdit = $item->exists;
        $formAction = $isEdit ? route('master.items.update', $item->id) : route('master.items.store');
        $input = 'mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs';
        $label = 'block text-xs font-medium text-gray-900';
        $section = 'mb-4 mt-6 border-b border-gray-200 pb-2 text-sm font-semibold text-gray-700';
    @endphp

    <div class="mx-auto max-w-4xl">
        @if ($errors->any())
            <div id="validation-alert"
                class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 shadow-sm transition-all duration-500">
                <div class="flex items-start justify-between">
                    <div class="flex items-start">
                        <svg class="h-4 w-4 mr-2 mt-0.5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <div>
                            <div class="font-semibold text-red-700 text-sm">Please correct the following errors:</div>
                            <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                        class="ml-2 text-red-600 hover:text-red-800 flex-shrink-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" x-data="{ sellable: {{ old('is_sellable', $item->is_sellable) ? 'true' : 'false' }} }">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ $isEdit ? 'Edit Item' : 'New Item' }}</h1>
                            <p class="mt-1 text-sm text-blue-100">Defaults here pre-fill RFD and invoice lines; approvers
                                can still change them per line.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('master.item') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">Back</a>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">
                                {{ $isEdit ? 'Update Item' : 'Save Item' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    <h2 class="{{ $section }} !mt-0">General</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="{{ $label }}">Item Code <span class="text-red-500">*</span></label>
                            <input type="text" name="code" maxlength="255" required
                                value="{{ old('code', $item->code) }}" class="{{ $input }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Item Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" maxlength="255" required
                                value="{{ old('name', $item->name) }}" class="{{ $input }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Description</label>
                            <textarea name="description" rows="2" class="{{ $input }}">{{ old('description', $item->description) }}</textarea>
                        </div>
                        <div>
                            <label class="{{ $label }}">Unit of Measure</label>
                            <select name="uom_id" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected(old('uom_id', $item->uom_id) == $uom->id)>
                                        {{ $uom->code }} — {{ $uom->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Status <span class="text-red-500">*</span></label>
                            <select name="status" required class="{{ $input }}">
                                <option value="1" @selected((string) old('status', $item->status) === '1')>Active</option>
                                <option value="0" @selected((string) old('status', $item->status) === '0')>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <h2 class="{{ $section }}">Purchasing <span class="font-normal text-gray-400">— used on Requests
                            for Disbursement</span></h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Default GL Account (expense / asset)</label>
                            <select name="gl_account_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($purchaseAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('gl_account_id', $item->gl_account_id) == $account->id)>
                                        {{ $account->account_code }} — {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Default Unit Price</label>
                            <input type="number" step="0.01" min="0" name="default_unit_price"
                                value="{{ old('default_unit_price', $item->default_unit_price) }}"
                                class="{{ $input }} text-right">
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Default Tax</label>
                            <select name="default_tax_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($purchaseTaxes as $tax)
                                    <option value="{{ $tax->id }}" @selected(old('default_tax_id', $item->default_tax_id) == $tax->id)>
                                        {{ $tax->code }} — {{ $tax->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Default Unit of Measure</label>
                            <select name="purchase_uom_id" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected(old('purchase_uom_id', $item->purchase_uom_id) == $uom->id)>
                                        {{ $uom->code }} — {{ $uom->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h2 class="{{ $section }}">Selling <span class="font-normal text-gray-400">— used on Sales
                            Invoices</span></h2>
                    <label class="flex items-center gap-2 text-xs text-gray-700">
                        <input type="checkbox" name="is_sellable" value="1" x-model="sellable"
                            @checked(old('is_sellable', $item->is_sellable))>
                        This item can be sold to customers
                    </label>

                    <div x-show="sellable" x-cloak class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Revenue GL Account <span
                                    class="text-red-500">*</span></label>
                            <select name="sales_gl_account_id" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($revenueAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('sales_gl_account_id', $item->sales_gl_account_id) == $account->id)>
                                        {{ $account->account_code }} — {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Default Selling Price</label>
                            <input type="number" step="0.01" min="0" name="default_sales_price"
                                value="{{ old('default_sales_price', $item->default_sales_price) }}"
                                class="{{ $input }} text-right">
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Default Sales Tax</label>
                            <select name="default_sales_tax_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($salesTaxes as $tax)
                                    <option value="{{ $tax->id }}" @selected(old('default_sales_tax_id', $item->default_sales_tax_id) == $tax->id)>
                                        {{ $tax->code }} — {{ $tax->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">Pick the Output VAT tax for VATable sales. The
                                same VAT-type list contains both Input and Output VAT.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Default Unit of Measure</label>
                            <select name="sales_uom_id" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected(old('sales_uom_id', $item->sales_uom_id) == $uom->id)>
                                        {{ $uom->code }} — {{ $uom->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
