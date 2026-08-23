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

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" @submit="beforeSubmit">

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

                <!-- Header fields -->
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
                            @php
                                $selectedCurrencyId = $rfdHeader->exists
                                    ? $rfdHeader->currency_id ?? ''
                                    : $systemSettings['currency_id'] ?? null;
                            @endphp
                            <select name="currency_id" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2.5">
                                <option value="">Select currency&hellip;</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" @selected(old('currency_id', $selectedCurrencyId) == $currency->id)>{{ $currency->code }}
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

                <!-- Attachments Section -->
                <div class="px-8 py-6 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-700">Attachments</h2>
                        <button type="button" @click="addAttachment()"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                            + Add Attachment
                        </button>
                    </div>

                    {{-- <div class="space-y-2" x-show="attachments.length > 0">
                        <template x-for="(attachment, index) in attachments" :key="attachment._key">
                            <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-3 border border-gray-200">
                                <div class="flex-1">
                                    <input type="file" :name="`attachments[${index}][file]`"
                                        @change="handleFileSelect($event, index)"
                                        class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <input type="hidden" :name="`attachments[${index}][existing_id]`"
                                        x-model="attachment.existing_id">
                                    <input type="text" :name="`attachments[${index}][description]`"
                                        x-model="attachment.description" placeholder="Attachment description (optional)"
                                        class="mt-1 w-full rounded border border-gray-300 p-1.5 text-xs">
                                </div>
                                <div class="text-xs text-gray-500" x-show="attachment.file_name">
                                    <span x-text="attachment.file_name"></span>
                                    <span x-text="attachment.file_size"></span>
                                </div>
                                <button type="button" @click="removeAttachment(index)"
                                    class="text-red-500 hover:text-red-700 text-lg">&times;</button>
                            </div>
                        </template>
                    </div> --}}
                    <!-- New attachments to upload -->
                    <div class="space-y-2 mt-3" x-show="attachments.length > 0">
                        <template x-for="(attachment, index) in attachments" :key="attachment._key">
                            <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-3 border border-gray-200">
                                <div class="flex-1">
                                    <input type="file" :name="`attachment_files[${index}]`"
                                        @change="handleFileSelect($event, index)"
                                        class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <input type="text" :name="`attachment_descriptions[${index}]`"
                                        x-model="attachment.description" placeholder="Attachment description (optional)"
                                        class="mt-1 w-full rounded border border-gray-300 p-1.5 text-xs">
                                </div>
                                <div class="text-xs text-gray-500" x-show="attachment.file_name">
                                    <span x-text="attachment.file_name"></span>
                                    <span x-text="attachment.file_size"></span>
                                </div>
                                <button type="button" @click="removeAttachment(index)"
                                    class="text-red-500 hover:text-red-700 text-lg">&times;</button>
                            </div>
                        </template>
                    </div>

                    <!-- Existing attachments display -->
                    @if ($isEdit && $rfdHeader->attachments->count() > 0)
                        <div class="mt-3">
                            <h3 class="text-xs font-medium text-gray-700 mb-2">Current Attachments</h3>
                            @foreach ($rfdHeader->attachments as $attachment)
                                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2 border border-gray-200 mb-1"
                                    x-data="{ deleted: false }" x-show="!deleted">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                                            </path>
                                        </svg>
                                        <a href="{{ route('ap.rfd.download-attachment', $attachment->id) }}"
                                            target="_blank" class="text-xs text-blue-600 hover:text-blue-800">
                                            {{ $attachment->original_filename }}
                                        </a>
                                        <span
                                            class="text-xs text-gray-500">({{ number_format($attachment->file_size / 1024, 1) }}
                                            KB)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">{{ $attachment->description }}</span>
                                        <button type="button" @click="deleteAttachment({{ $attachment->id }}, $el)"
                                            class="text-red-500 hover:text-red-700 text-sm">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- For new attachments (not yet saved) -->
                    {{-- <template x-for="(attachment, index) in attachments" :key="attachment._key">
                        <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <div class="flex-1">
                                <input type="file" :name="`attachment_files[${index}]`"
                                    @change="handleFileSelect($event, index)"
                                    class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <input type="hidden" :name="`attachment_existing_ids[${index}]`"
                                    x-model="attachment.existing_id">
                                <input type="text" :name="`attachment_descriptions[${index}]`"
                                    x-model="attachment.description" placeholder="Attachment description (optional)"
                                    class="mt-1 w-full rounded border border-gray-300 p-1.5 text-xs">
                            </div>
                            <div class="text-xs text-gray-500" x-show="attachment.file_name">
                                <span x-text="attachment.file_name"></span>
                                <span x-text="attachment.file_size"></span>
                            </div>
                            <!-- For new attachments, just remove from the array -->
                            <button type="button" @click="removeAttachment(index)"
                                class="text-red-500 hover:text-red-700 text-lg">&times;</button>
                        </div>
                    </template> --}}
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
                                                x-model="line.reference"
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
                                            x-text="formatNumber(line.total_amount)"></td>
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
                                    <td class="px-3 py-2 text-right tabular-nums" x-text="formatNumber(subtotal)"></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="px-3 py-2 text-right">Total Tax</td>
                                    <td class="px-3 py-2 text-right tabular-nums" x-text="formatNumber(totalTax)"></td>
                                    <td></td>
                                </tr>
                                <tr class="border-t border-gray-200">
                                    <td colspan="8" class="px-3 py-2 text-right text-sm">Total Due</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sm font-bold"
                                        x-text="formatNumber(totalDue)"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // System settings from PHP (passed from controller via trait)
        const SYSTEM_SETTINGS = @json($systemSettings);

        // Get tax rate from DOM
        function getTaxRate(taxId) {
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

        // Currency format helper using system settings
        function formatCurrency(amount) {
            if (amount === undefined || amount === null || isNaN(amount)) {
                return '0.00';
            }

            const decimal = SYSTEM_SETTINGS.decimal_places || 2;
            const thousands = SYSTEM_SETTINGS.thousands_separator || ',';
            const decimalPoint = SYSTEM_SETTINGS.decimal_separator || '.';

            let formatted = Number(amount).toFixed(decimal);
            let parts = formatted.split('.');
            let integerPart = parts[0];
            const decimalPart = parts[1] || '';

            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousands);

            return integerPart + decimalPoint + decimalPart;
        }

        function rfdBuilder() {
            return {
                submitForApproval: false,
                lines: [],
                attachments: [],

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

                    this.$nextTick(() => {
                        this.lines.forEach((l) => {
                            this.recalcLine(l);
                        });
                    });

                    // Initialize attachments array
                    this.attachments = [];
                },

                formatNumber(value) {
                    return formatCurrency(value);
                },

                addAttachment() {
                    this.attachments.push({
                        _key: crypto.randomUUID(),
                        file_name: '',
                        file_size: '',
                        description: '',
                        file: null,
                    });
                },

                removeAttachment(index) {
                    this.attachments.splice(index, 1);
                },

                async deleteAttachment(id, rowEl) {
                    if (!confirm('Delete this attachment?')) return;

                    try {
                        const res = await fetch(`/ap/rfd/attachment/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        if (!res.ok) throw new Error('Delete failed');

                        rowEl.remove();
                    } catch (e) {
                        alert('Could not delete attachment. Please try again.');
                    }
                },

                handleFileSelect(event, index) {
                    const file = event.target.files[0];
                    if (file) {
                        this.attachments[index].file_name = file.name;
                        this.attachments[index].file_size = (file.size / 1024).toFixed(1) + ' KB';
                        this.attachments[index].file = file;
                    }
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

                recalcLine(line) {
                    const quantity = Number(line.quantity) || 0;
                    const unitPrice = Number(line.unit_price) || 0;
                    const discount = Number(line.discount_amount) || 0;
                    const taxable = (quantity * unitPrice) - discount;

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
                    // Submit handler
                },
            };
        }
    </script>
@endsection
