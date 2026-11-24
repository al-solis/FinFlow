@extends('dashboard')

@section('title', $memo->exists ? 'Edit Debit Memo' : 'New Debit Memo')

@section('content')
    @php
        $isEdit = $memo->exists;
        $formAction = $isEdit ? route('ar.dm.update', $memo->id) : route('ar.dm.store');
        $input = 'mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs';
        $cell = 'w-full rounded border border-gray-300 p-1.5 text-xs';
        $label = 'block text-xs font-medium text-gray-900';
        $oldLines = old('lines');
        $lines = is_array($oldLines) ? array_values($oldLines) : $initialLines ?? [];
        $dateOf = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '';
    @endphp

    <div class="mx-auto max-w-7xl">
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

        @if (!($hasWorkflow ?? true))
            <div class="mt-3 mb-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800">
                <strong>Warning:</strong> No approval workflow is configured for Debit Memos. You can save a draft,
                but you cannot submit for approval until one is set up in the Workflow module.
            </div>
        @endif

        @if (!empty($returnRemark))
            <div class="mt-3 mb-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800">
                <strong>Returned by approver:</strong> {{ $returnRemark->remarks }}
                <span class="text-xs text-orange-600">— {{ $returnRemark->acted_at?->format('M d, Y g:i A') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" x-data="memoForm({
            type: 'debit',
            seriesOptions: @js($seriesOptions ?? []),
            seriesId: @js((string) old('series_id', $memo->series_id ?? ($memo->invoice_series_id ?? ''))),
            customers: @js($customerOptions ?? []),
            invoices: @js($invoiceOptions ?? []),
            accounts: @js($accounts ?? []),
            vatOptions: @js($vatOptions ?? []),
            taxOption: @js((int) old('tax_option', $memo->tax_option ?? 0)),
            lines: @js($lines),
            customerId: @js((string) old('customer_id', $memo->customer_id ?? '')),
            invoiceId: @js((string) old('invoice_id', $memo->invoice_id ?? '')),
            currencyId: @js((string) old('currency_id', $memo->currency_id ?? '')),
            memoDate: @js((string) old('memo_date', $dateOf($memo->memo_date ?? ($memo->debit_memo_date ?? now()))))
        })">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 class="text-2xl font-bold text-white">
                                {{ $isEdit ? 'Edit Debit Memo' : 'New Debit Memo' }}</h1>
                            <p class="mt-1 text-sm text-blue-100">
                                Final approval increases Accounts Receivable and records revenue / output VAT.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ $isEdit ? route('ar.dm.show', $memo) : route('ar.dmemo') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">Back</a>
                            <button type="submit" name="action" value="draft"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 hover:bg-white">
                                Save Draft
                            </button>
                            <button type="submit" name="action" value="submit" @disabled(!($hasWorkflow ?? true))
                                title="{{ $hasWorkflow ?? true ? '' : 'An approval workflow must be configured first' }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg disabled:opacity-50">
                                Submit for Approval
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Customer <span class="text-red-500">*</span></label>
                            <select name="customer_id" required x-model="customerId" @change="onCustomerChange()"
                                class="{{ $input }}">
                                <option value="">— Select Customer —</option>
                                <template x-for="c in customers" :key="c.id">
                                    <option :value="c.id" :disabled="c.credit_hold"
                                        x-text="c.code + ' — ' + c.name + (c.credit_hold ? ' (credit hold)' : '')"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="{{ $label }}">Debit Memo Date <span class="text-red-500">*</span></label>
                            <input type="date" name="memo_date" required x-model="memoDate" class="{{ $input }}">
                        </div>

                        <div>
                            <label class="{{ $label }}">Reference Invoice</label>
                            <select name="invoice_id" x-model="invoiceId" @change="onInvoiceChange()"
                                class="{{ $input }}">
                                <option value="">— None / Standalone —</option>
                                <template x-for="inv in filteredInvoices()" :key="inv.id">
                                    <option :value="inv.id" x-text="inv.no + ' (Bal: ' + fmt(inv.balance) + ')'">
                                    </option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="{{ $label }}">Series</label>
                            <select name="series_id" x-model="seriesId" class="{{ $input }}">
                                <template x-for="s in seriesOptions" :key="s.id">
                                    <option :value="s.id" x-text="s.code + (s.is_default ? ' (default)' : '')">
                                    </option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="{{ $label }}">Reason Code <span class="text-red-500">*</span></label>
                            <select name="reason_code" required class="{{ $input }}">
                                @php $reasonVal = old('reason_code', $memo->reason_code ?? 'underbilling'); @endphp
                                <option value="underbilling" @selected($reasonVal === 'underbilling')>Underbilling / Price Adjustment
                                </option>
                                <option value="additional_charge" @selected($reasonVal === 'additional_charge')>Additional Freight / Service
                                    Charge</option>
                                <option value="penalty_fee" @selected($reasonVal === 'penalty_fee')>Late / Debit Fee</option>
                                <option value="other" @selected($reasonVal === 'other')>Other Adjustment</option>
                            </select>
                        </div>

                        <div>
                            <label class="{{ $label }}">Currency</label>
                            <select name="currency_id" x-model="currencyId" class="{{ $input }}">
                                <option value="">— Default —</option>
                                @foreach ($currencies ?? [] as $currency)
                                    <option value="{{ $currency->id }}">{{ $currency->code }} — {{ $currency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="{{ $label }}">Exchange Rate</label>
                            <input type="number" step="0.000001" min="0.000001" name="exchange_rate"
                                value="{{ old('exchange_rate', $memo->exchange_rate ?? 1) }}"
                                class="{{ $input }} text-right">
                        </div>

                        <div>
                            <label class="{{ $label }}">Tax Mode <span class="text-red-500">*</span></label>
                            <select name="tax_option" required x-model.number="taxOption" class="{{ $input }}">
                                <option value="0">No Tax</option>
                                <option value="1">Tax Inclusive (price includes VAT)</option>
                                <option value="2">Tax Exclusive (add VAT to price)</option>
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="{{ $label }}">Reason / Remarks</label>
                            <input type="text" name="remarks" maxlength="2000"
                                value="{{ old('remarks', $memo->remarks ?? '') }}" class="{{ $input }}">
                        </div>
                    </div>

                    <div class="mt-6 mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Line Items</h3>
                        <button type="button" @click="addLine()"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                            + Add Line
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-[1040px] w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-2 py-2 text-left w-6">#</th>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-left w-48">Revenue Account</th>
                                    <th class="px-2 py-2 text-right w-20">Qty</th>
                                    <th class="px-2 py-2 text-right w-28">Unit Price</th>
                                    <th class="px-2 py-2 text-right w-24">Discount</th>
                                    <th class="px-2 py-2 text-left w-44">VAT</th>
                                    <th class="px-2 py-2 text-right w-24">Tax</th>
                                    <th class="px-2 py-2 text-right w-28">Line Total</th>
                                    <th class="px-2 py-2 w-6"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, index) in lines" :key="line._key">
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-2 py-2 text-gray-400" x-text="index + 1"></td>
                                        <td class="px-2 py-2">
                                            <input type="text" :name="`lines[${index}][description]`"
                                                x-model="line.description" required maxlength="1000"
                                                class="{{ $cell }}">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select :name="`lines[${index}][gl_account_id]`" required
                                                x-model="line.gl_account_id" class="{{ $cell }}">
                                                <option value="">Select account…</option>
                                                <template x-for="a in accounts" :key="a.id">
                                                    <option :value="a.id"
                                                        x-text="a.account_code + ' — ' + a.account_name"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" step="0.000001" min="0.000001"
                                                :name="`lines[${index}][quantity]`" x-model.number="line.quantity"
                                                required class="{{ $cell }} text-right">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" step="0.000001" min="0"
                                                :name="`lines[${index}][unit_price]`" x-model.number="line.unit_price"
                                                required class="{{ $cell }} text-right">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" step="0.01" min="0"
                                                :name="`lines[${index}][discount_amount]`"
                                                x-model.number="line.discount_amount"
                                                class="{{ $cell }} text-right">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select :name="`lines[${index}][vat]`" required x-model="line.vat"
                                                class="{{ $cell }}">
                                                <template x-for="o in vatOptions" :key="o.value">
                                                    <option :value="o.value" x-text="o.label"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-gray-600"
                                            x-text="fmt(taxAmount(line))"></td>
                                        <td class="px-2 py-2 text-right tabular-nums font-medium text-gray-800"
                                            x-text="fmt(lineTotal(line))"></td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" x-show="lines.length > 1"
                                                @click.prevent="removeLine(index)"
                                                class="text-red-500 hover:text-red-700">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <table class="w-full max-w-sm text-xs">
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="py-1.5 text-gray-500">Taxable Base</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(taxableTotal)"></td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500">Output VAT</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(vatAmount)"></td>
                                </tr>
                                <tr class="font-bold text-gray-900">
                                    <td class="py-2">Total Debit Memo</td>
                                    <td class="py-2 text-right tabular-nums" x-text="'₱' + fmt(total)"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                        <strong>Journal preview (on final approval):</strong>
                        Dr Accounts Receivable <span x-text="fmt(total)"></span>
                        &nbsp;·&nbsp; Cr Revenue <span x-text="fmt(taxableTotal)"></span>
                        &nbsp;·&nbsp; Cr Output VAT <span x-text="fmt(vatAmount)"></span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @include('ar.partials.memo-form-script')
@endsection
