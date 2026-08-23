@extends('dashboard')

@section('title', $rfdHeader->exists ? 'Edit Request for Disbursement' : 'New Request for Disbursement')

@section('content')
    <div class="mx-auto max-w-7xl" x-data="rfdBuilder()">
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
            $isEdit = $rfdHeader->exists;
            $formAction = $isEdit ? route('ap.rfd.update', $rfdHeader->id) : route('ap.rfd.store');
        @endphp

        <form method="POST" action="{{ $formAction }}" @submit="beforeSubmit">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="submit_for_approval" x-model="submitForApproval">

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">Request for Disbursement</h1>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ $isEdit ? 'Update this request' : 'Fill in terms and line items' }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('ap.rfd') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 transition-all duration-200 backdrop-blur-sm border border-white/20">
                                Back
                            </a>
                            <button type="submit" @click="submitForApproval = false"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 hover:bg-white transition-all duration-200">
                                Save Draft
                            </button>
                            <button type="submit" @click="submitForApproval = true"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg hover:shadow-xl transition-all duration-200">
                                Submit for Approval
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Header fields (vendor removed — now set per line below) -->
                <div class="px-8 py-6 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4">Request Details</h2>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-900">Request Date <span
                                    class="text-red-500">*</span></label>
                            <input type="date" name="request_date"
                                value="{{ old('request_date', optional($rfdHeader->request_date)->format('Y-m-d')) }}"
                                required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5"
                                @if ($isEdit) readonly
                                @else
                                    min="{{ now()->format('Y-m-d') }}"
                                    max="{{ now()->format('Y-m-d') }}" @endif>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">Required Date</label>
                            <input type="date" name="required_date"
                                value="{{ old('required_date', optional($rfdHeader->required_date)->format('Y-m-d')) }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">Term</label>
                            <select name="term_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5">
                                <option value="">Select term&hellip;</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}" @selected(old('term_id', $rfdHeader->term_id) == $term->id)>{{ $term->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">Payment Method</label>
                            <select name="payment_method_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5">
                                <option value="">Select method&hellip;</option>
                                @foreach ($paymentMethods as $method)
                                    <option value="{{ $method->id }}" @selected(old('payment_method_id', $rfdHeader->payment_method_id) == $method->id)>{{ $method->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">Currency <span
                                    class="text-red-500">*</span></label>
                            <select name="currency_id" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5">
                                <option value="">Select currency&hellip;</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" @selected(old('currency_id', $rfdHeader->currency_id) == $currency->id)>{{ $currency->code }}
                                        - {{ $currency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-900">Exchange Rate</label>
                            <input type="number" step="0.0001" name="exchange_rate"
                                value="{{ old('exchange_rate', $rfdHeader->exchange_rate ?? 1) }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-900">Remarks</label>
                            <input type="text" name="remarks" value="{{ old('remarks', $rfdHeader->remarks) }}"
                                placeholder="Purpose of this disbursement"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5">
                        </div>
                    </div>
                </div>

                <!-- Line items -->
                <div class="px-8 py-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-700">Line Items</h2>
                        <button type="button" @click="addLine()"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                            + Add Line
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left w-8">#</th>
                                    <th class="px-3 py-2 text-left w-[200px]">Vendor</th>
                                    <th class="px-3 py-2 text-left w-[200px]">Description</th>
                                    <th class="px-3 py-2 text-left w-[200px]">GL Account</th>
                                    <th class="px-3 py-2 text-right w-[150px]">Reference</th>
                                    <th class="px-3 py-2 text-right w-20">Qty</th>
                                    <th class="px-3 py-2 text-right w-28">Unit Price</th>
                                    <th class="px-3 py-2 text-right w-28">Discount</th>
                                    <th class="px-3 py-2 text-left min-w-[160px]">Taxes</th>
                                    <th class="px-3 py-2 text-right w-28">Line Total</th>
                                    <th class="px-3 py-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, index) in lines" :key="line._key">
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-3 py-2 text-gray-400" x-text="index + 1"></td>
                                        <td class="px-3 py-2">
                                            <select :name="`lines[${index}][vendor_id]`" x-model="line.vendor_id" required
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                                <option value="">Select vendor&hellip;</option>
                                                @foreach ($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`lines[${index}][description]`"
                                                x-model="line.description" required
                                                placeholder="Item / expense description"
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                        </td>
                                        <td class="px-3 py-2">
                                            <select :name="`lines[${index}][gl_account_id]`" x-model="line.gl_account_id"
                                                required class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                                <option value="">Select account&hellip;</option>
                                                @foreach ($glAccounts as $account)
                                                    <option value="{{ $account->id }}">
                                                        {{ $account->account_code }} &mdash; {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" maxlength="50" :name="`lines[${index}][reference]`"
                                                x-model.number="line.reference"
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0"
                                                :name="`lines[${index}][quantity]`" x-model.number="line.quantity"
                                                @input="recalcLine(line)"
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0"
                                                :name="`lines[${index}][unit_price]`" x-model.number="line.unit_price"
                                                @input="recalcLine(line)"
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0"
                                                :name="`lines[${index}][discount_amount]`"
                                                x-model.number="line.discount_amount" @input="recalcLine(line)"
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="space-y-1">
                                                <template x-for="(taxId, tIndex) in line.tax_ids"
                                                    :key="line._key + '-tax-' + tIndex">
                                                    <div class="flex items-center gap-1">
                                                        <select :name="`lines[${index}][tax_ids][]`"
                                                            x-model="line.tax_ids[tIndex]" @change="recalcLine(line)"
                                                            class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                                            <option value="">Select tax&hellip;</option>
                                                            @foreach ($taxes as $tax)
                                                                <option value="{{ $tax->id }}"
                                                                    data-rate="{{ $tax->rate }}"
                                                                    data-fixed="{{ $tax->fixed_amount }}">
                                                                    {{ $tax->code }} &mdash; {{ $tax->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button"
                                                            @click.prevent="line.tax_ids.splice(tIndex, 1); recalcLine(line)"
                                                            class="text-red-500 hover:text-red-700 text-xs">&times;</button>
                                                    </div>
                                                </template>
                                                <button type="button" @click.prevent="line.tax_ids.push('')"
                                                    class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                                    + Add tax
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-700"
                                            x-text="line.total_amount.toFixed(2)"></td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" x-show="lines.length > 1"
                                                @click.prevent="removeLine(index)"
                                                class="text-red-500 hover:text-red-700">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 font-medium text-gray-700">
                                <tr>
                                    <td colspan="8" class="px-3 py-2 text-right">Subtotal</td>
                                    <td class="px-3 py-2 text-right tabular-nums" x-text="subtotal.toFixed(2)"></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="px-3 py-2 text-right">Total Tax</td>
                                    <td class="px-3 py-2 text-right tabular-nums" x-text="totalTax.toFixed(2)"></td>
                                    <td></td>
                                </tr>
                                <tr class="border-t border-gray-200">
                                    <td colspan="8" class="px-3 py-2 text-right text-sm">Total Due</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sm" x-text="totalDue.toFixed(2)">
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- <script>
        // Tax rate/fixed-amount lookup, built once from the <select> options
        // rendered server-side (works for both initial page load and lines
        // added dynamically afterwards, since the <option> tags are already
        // present in every tax <select> — Alpine just clones the template).
        const TAX_RATES = {};
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('select[name$="[tax_ids][]"] option').forEach(opt => {
                if (opt.value) {
                    TAX_RATES[opt.value] = {
                        rate: parseFloat(opt.dataset.rate || 0),
                        fixed: parseFloat(opt.dataset.fixed || 0),
                    };
                }
            });
        });

        function rfdBuilder() {
            return {
                submitForApproval: false,
                lines: [],

                init() {
                    // Build the reactive lines array exactly once, from the
                    // server-provided data. Re-running this (e.g. if Alpine
                    // ever re-inits the component) would double every line —
                    // guard against that explicitly.
                    if (this.lines.length > 0) {
                        return;
                    }

                    const serverLines = @js($lines);

                    this.lines = serverLines.map(l => ({
                        _key: l._key || crypto.randomUUID(),
                        id: l.id ?? null,
                        vendor_id: l.vendor_id ?? '',
                        description: l.description ?? '',
                        gl_account_id: l.gl_account_id ?? '',
                        quantity: parseFloat(l.quantity ?? 1),
                        unit_price: parseFloat(l.unit_price ?? 0),
                        discount_amount: parseFloat(l.discount_amount ?? 0),
                        tax_ids: (l.taxes || []).map(t => String(t.tax_id)),
                        taxable_amount: 0,
                        tax_amount: 0,
                        total_amount: 0,
                    }));

                    this.lines.forEach(l => this.recalcLine(l));
                },

                addLine() {
                    this.lines.push({
                        _key: crypto.randomUUID(),
                        id: null,
                        vendor_id: '',
                        description: '',
                        gl_account_id: '',
                        quantity: 1,
                        unit_price: 0,
                        discount_amount: 0,
                        tax_ids: [],
                        taxable_amount: 0,
                        tax_amount: 0,
                        total_amount: 0,
                    });
                },

                removeLine(index) {
                    this.lines.splice(index, 1);
                },

                recalcLine(line) {
                    const taxable = (Number(line.quantity) || 0) * (Number(line.unit_price) || 0) - (Number(line
                        .discount_amount) || 0);
                    let tax = 0;
                    (line.tax_ids || []).forEach(taxId => {
                        const meta = TAX_RATES[taxId];
                        if (!meta) return;
                        tax += meta.fixed > 0 ? meta.fixed : taxable * (meta.rate / 100);
                    });
                    line.taxable_amount = taxable;
                    line.tax_amount = tax;
                    line.total_amount = taxable + tax;
                },

                get subtotal() {
                    return this.lines.reduce((sum, l) => sum + (l.taxable_amount || 0), 0);
                },
                get totalTax() {
                    return this.lines.reduce((sum, l) => sum + (l.tax_amount || 0), 0);
                },
                get totalDue() {
                    return this.subtotal + this.totalTax;
                },

                beforeSubmit() {
                    // submitForApproval is set by whichever button was clicked before this fires
                },
            };
        }
    </script> --}}

    <script>
        // Tax rate/fixed-amount lookup - will be updated dynamically
        function getTaxRate(taxId) {
            // Try to find the tax rate from any select option in the DOM
            const select = document.querySelector(`select[name*="tax_ids"][name$="[${taxId}]"]`);
            if (select) {
                const option = select.querySelector(`option[value="${taxId}"]`);
                if (option) {
                    return {
                        rate: parseFloat(option.dataset.rate || 0),
                        fixed: parseFloat(option.dataset.fixed || 0),
                    };
                }
            }

            // Fallback: look for any option with this value anywhere in the page
            const allOptions = document.querySelectorAll('select[name*="tax_ids"] option[value="' + taxId + '"]');
            for (const opt of allOptions) {
                if (opt.value === taxId) {
                    return {
                        rate: parseFloat(opt.dataset.rate || 0),
                        fixed: parseFloat(opt.dataset.fixed || 0),
                    };
                }
            }

            return {
                rate: 0,
                fixed: 0
            };
        }

        function rfdBuilder() {
            return {
                submitForApproval: false,
                lines: [],

                init() {
                    if (this.lines.length > 0) {
                        return;
                    }

                    const serverLines = @js($lines);

                    this.lines = serverLines.map(l => ({
                        _key: l._key || crypto.randomUUID(),
                        id: l.id ?? null,
                        vendor_id: l.vendor_id ?? '',
                        description: l.description ?? '',
                        gl_account_id: l.gl_account_id ?? '',
                        reference: l.reference ?? '',
                        quantity: parseFloat(l.quantity ?? 1),
                        unit_price: parseFloat(l.unit_price ?? 0),
                        discount_amount: parseFloat(l.discount_amount ?? 0),
                        tax_ids: (l.taxes || []).map(t => String(t.tax_id)),
                        taxable_amount: 0,
                        tax_amount: 0,
                        total_amount: 0,
                    }));

                    // Initialize calculations
                    this.$nextTick(() => {
                        this.lines.forEach((l, index) => {
                            this.recalcLine(l, index);
                        });
                    });
                },

                addLine() {
                    this.lines.push({
                        _key: crypto.randomUUID(),
                        id: null,
                        vendor_id: '',
                        description: '',
                        gl_account_id: '',
                        reference: '',
                        quantity: 1,
                        unit_price: 0,
                        discount_amount: 0,
                        tax_ids: [],
                        taxable_amount: 0,
                        tax_amount: 0,
                        total_amount: 0,
                    });
                },

                removeLine(index) {
                    this.lines.splice(index, 1);
                },

                recalcLine(line, index) {
                    // Calculate taxable amount
                    const quantity = Number(line.quantity) || 0;
                    const unitPrice = Number(line.unit_price) || 0;
                    const discount = Number(line.discount_amount) || 0;
                    const taxable = (quantity * unitPrice) - discount;

                    // Calculate tax
                    let tax = 0;
                    if (line.tax_ids && line.tax_ids.length > 0) {
                        line.tax_ids.forEach(taxId => {
                            if (taxId) {
                                const meta = getTaxRate(taxId);
                                if (meta) {
                                    if (meta.fixed > 0) {
                                        tax += meta.fixed;
                                    } else if (meta.rate > 0) {
                                        tax += taxable * (meta.rate / 100);
                                    }
                                }
                            }
                        });
                    }

                    // Update line properties
                    line.taxable_amount = taxable;
                    line.tax_amount = tax;
                    line.total_amount = taxable + tax;
                },

                get subtotal() {
                    return this.lines.reduce((sum, l) => sum + (l.taxable_amount || 0), 0);
                },

                get totalTax() {
                    return this.lines.reduce((sum, l) => sum + (l.tax_amount || 0), 0);
                },

                get totalDue() {
                    return this.subtotal + this.totalTax;
                },

                beforeSubmit() {
                    // This is called before form submission
                    // submitForApproval is set by whichever button was clicked
                },
            };
        }
    </script>
@endsection
