@extends('dashboard')

@section('title', $invoice->exists ? 'Edit Sales Invoice' : 'New Sales Invoice')

@section('content')
    @php
        $isEdit = $invoice->exists;
        $formAction = $isEdit ? route('ar.invoices.update', $invoice->id) : route('ar.invoices.store');
        $input = 'mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs';
        $cell = 'w-full rounded border border-gray-300 p-1.5 text-xs';
        $label = 'block text-xs font-medium text-gray-900';
        $oldLines = old('lines');
        $lines = is_array($oldLines) ? array_values($oldLines) : $initialLines;
        $dateOf = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '';
        $recordDiscountInGl = (bool) ($arSettings->record_sales_discount_in_gl ?? false);
        $defaultDiscountAccountId = $arSettings->default_sales_discount_account_id ?? null;
        $discountAccountSet =
            (bool) ($invoice->customer_id &&
                ($arSettings->default_sales_discount_account_id ||
                    optional($invoice->customer)->default_sales_discount_account_id));
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

        @if (!$hasWorkflow)
            <div class="mt-3 mb-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800">
                <strong>Warning:</strong> No approval workflow is configured for Sales Invoices. You can save a draft,
                but you cannot submit for approval until one is set up in the Workflow module.
            </div>
        @endif

        @if ($returnRemark)
            <div class="mt-3 mb-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800">
                <strong>Returned by approver:</strong> {{ $returnRemark->remarks }}
                <span class="text-xs text-orange-600">— {{ $returnRemark->acted_at?->format('M d, Y g:i A') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" x-data="invoiceForm({
            seriesOptions: @js($seriesOptions),
            invoiceSeriesId: @js((string) old('invoice_series_id', $invoice->invoice_series_id ?? '')),
            customers: @js($customerOptions),
            terms: @js($terms),
            items: @js($itemOptions),
            uoms: @js($uomOptions),
            accounts: @js($accounts),
            vatOptions: @js($vatOptions),
            taxOption: @js((int) old('tax_option', $invoice->tax_option ?? 0)),
            recordDiscountInGl: @js($recordDiscountInGl),
            defaultDiscountAccountId: @js($defaultDiscountAccountId),
            lines: @js($lines),
            customerId: @js((string) old('customer_id', $invoice->customer_id)),
            termId: @js((string) old('term_id', $invoice->term_id)),
            currencyId: @js((string) old('currency_id', $invoice->currency_id)),
            invoiceDate: @js((string) old('invoice_date', $dateOf($invoice->invoice_date))),
            dueDate: @js((string) old('due_date', $dateOf($invoice->due_date)))
        })">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">
                                {{ $isEdit ? 'Edit Sales Invoice' : 'New Sales Invoice' }}</h1>
                            <p class="mt-1 text-sm text-blue-100">The BIR invoice number is issued when the invoice is
                                approved.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ $isEdit ? route('ar.invoices.show', $invoice) : route('ar.inv') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">Back</a>
                            <button type="submit" name="action" value="draft"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 hover:bg-white">
                                Save Draft
                            </button>
                            <button type="submit" name="action" value="submit" @disabled(!$hasWorkflow)
                                title="{{ $hasWorkflow ? '' : 'An approval workflow must be configured first' }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg disabled:opacity-50">
                                Submit for Approval
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    {{-- Header --}}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Customer <span class="text-red-500">*</span></label>
                            <select name="customer_id" required x-model="customerId" @change="onCustomerChange()"
                                class="{{ $input }}">
                                <option value="">— Select Customer —</option>
                                <template x-for="c in customers" :key="c.id">
                                    <option :value="c.id" :disabled="c.credit_hold"
                                        :selected="String(c.id) === String(customerId)"
                                        x-text="c.code + ' — ' + c.name + (c.credit_hold ? ' (credit hold)' : '')">
                                    </option>
                                </template>
                            </select>
                            <p x-show="creditWarning" x-cloak class="mt-1 text-[11px] font-medium text-red-600"
                                x-text="creditWarning"></p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Invoice Date <span class="text-red-500">*</span></label>
                            <input type="date" name="invoice_date" required x-model="invoiceDate"
                                @change="recomputeDue()" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Customer PO No.</label>
                            <input type="text" name="customer_po_no" maxlength="50"
                                value="{{ old('customer_po_no', $invoice->customer_po_no) }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Invoice Series</label>
                            <select name="invoice_series_id" x-model="invoiceSeriesId" class="{{ $input }}">
                                <template x-for="s in seriesOptions" :key="s.id">
                                    <option :value="s.id" :selected="String(s.id) === String(invoiceSeriesId)"
                                        x-text="s.code + (s.is_default ? ' (default)' : '')"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">The actual number is assigned when the invoice is
                                approved.</p>
                        </div>

                        <div>
                            <label class="{{ $label }}">Payment Terms</label>
                            <select name="term_id" x-model="termId" @change="recomputeDue()" class="{{ $input }}">
                                <option value="">— Select —</option>
                                <template x-for="t in terms" :key="t.id">
                                    <option :value="t.id" :selected="String(t.id) === String(termId)"
                                        x-text="t.name">
                                    </option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Due Date</label>
                            <input type="date" name="due_date" x-model="dueDate" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Currency</label>
                            <select name="currency_id" x-model="currencyId" class="{{ $input }}">
                                <option value="">— Default —</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}">{{ $currency->code }} — {{ $currency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Exchange Rate</label>
                            <input type="number" step="0.000001" min="0.000001" name="exchange_rate"
                                value="{{ old('exchange_rate', $invoice->exchange_rate ?? 1) }}"
                                class="{{ $input }} text-right">
                        </div>
                        <div>
                            <label class="{{ $label }}">Tax Mode <span class="text-red-500">*</span></label>
                            <select name="tax_option" required x-model.number="taxOption" class="{{ $input }}"
                                @change="if (lineHasVat()) confirm('Changing the tax mode will re-interpret existing unit prices. Continue?') || $event.preventDefault()">
                                <option value="0">No Tax</option>
                                <option value="1">Tax Inclusive (price includes VAT)</option>
                                <option value="2">Tax Exclusive (add VAT to price)</option>
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400" x-show="taxOption === 0">VAT classes on lines are kept
                                for BIR reporting, but no VAT is
                                computed.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Billing Period From</label>
                            <input type="date" name="billing_period_from"
                                value="{{ old('billing_period_from', $dateOf($invoice->billing_period_from)) }}"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Billing Period To</label>
                            <input type="date" name="billing_period_to"
                                value="{{ old('billing_period_to', $dateOf($invoice->billing_period_to)) }}"
                                class="{{ $input }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Remarks</label>
                            <input type="text" name="remarks" maxlength="2000"
                                value="{{ old('remarks', $invoice->remarks) }}" class="{{ $input }}">
                        </div>
                    </div>

                    {{-- Lines --}}
                    <div class="mt-6 mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Line Items</h3>
                        <button type="button" @click="addLine()"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">+
                            Add Line</button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-[1180px] w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-2 py-2 text-left w-6">#</th>
                                    <th class="px-2 py-2 text-left w-44">Item</th>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-left w-44">Revenue Account</th>
                                    <th class="px-2 py-2 text-right w-20">Qty</th>
                                    <th class="px-2 py-2 text-left w-24">UOM</th>
                                    <th class="px-2 py-2 text-right w-28">Unit Price</th>
                                    <th class="px-2 py-2 text-right w-24">Discount</th>
                                    <th class="px-2 py-2 text-left w-40">VAT</th>
                                    <th class="px-2 py-2 text-right w-28">Line Total</th>
                                    <th class="px-2 py-2 w-6"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, index) in lines" :key="line._key">
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-2 py-2 text-gray-400" x-text="index + 1"></td>
                                        <td class="px-2 py-2">
                                            <select :name="`lines[${index}][item_id]`"
                                                @change="line.item_id = $event.target.value; onItemChange(line)"
                                                class="{{ $cell }}">
                                                <option value="">— Free text —</option>
                                                <template x-for="it in items" :key="it.id">
                                                    <option :value="it.id" x-text="it.code + ' — ' + it.name"
                                                        :selected="String(it.id) === String(line.item_id)"></option>
                                                </template>
                                            </select>
                                            {{-- FIX: warn when the picked item has no selling defaults, so
                                                 the line not auto-filling is expected, not a bug. --}}
                                            <p class="mt-0.5 text-[10px] text-orange-600"
                                                x-show="line.item_id && !item(line)?.has_sales_defaults" x-cloak>
                                                Item has no selling defaults — fill in manually.
                                            </p>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" :name="`lines[${index}][description]`"
                                                x-model="line.description" required maxlength="1000"
                                                class="{{ $cell }}">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select :name="`lines[${index}][gl_account_id]`" required
                                                @change="line.gl_account_id = $event.target.value"
                                                class="{{ $cell }}">
                                                <option value="">Select account…</option>
                                                <template x-for="a in accounts" :key="a.id">
                                                    <option :value="a.id"
                                                        x-text="a.account_code + ' — ' + a.account_name"
                                                        :selected="String(a.id) === String(line.gl_account_id)"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" step="0.000001" min="0.000001"
                                                :name="`lines[${index}][quantity]`" x-model.number="line.quantity"
                                                required class="{{ $cell }} text-right">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select :name="`lines[${index}][uom_id]`"
                                                @change="onUomChange(line, $event.target.value)"
                                                class="{{ $cell }}">
                                                <option value="">—</option>
                                                <template x-for="u in uomChoices(line)" :key="u.id">
                                                    <option :value="u.id" x-text="u.code"
                                                        :selected="String(u.id) === String(line.uom_id)"></option>
                                                </template>
                                            </select>
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
                                            <select :name="`lines[${index}][vat]`" required
                                                @change="line.vat = $event.target.value" class="{{ $cell }}">
                                                <template x-for="o in vatOptions" :key="o.value">
                                                    <option :value="o.value" x-text="o.label"
                                                        :selected="line.vat === o.value"></option>
                                                </template>
                                            </select>
                                            <p class="mt-0.5 text-[10px] text-gray-400" x-show="taxOption !== 0"
                                                x-text="taxOption === 1 ? 'VAT included in price' : 'VAT added on top'">
                                            </p>
                                        </td>
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
                    <p class="mt-2 text-[11px] text-gray-400">Pick the <strong>Output</strong> VAT for VATable sales. The
                        list also contains Input VAT because both share the VAT tax type. Withholding tax is entered on
                        the collection, not here.</p>

                    {{-- Totals --}}
                    <div class="mt-4 flex justify-end">
                        <table class="w-full max-w-sm text-xs">
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="py-1.5 text-gray-500">Subtotal</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(subtotal)"></td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500">Less: Discount</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(discountTotal)"></td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500">VATable Sales</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(vatableSales)"></td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500">Zero-Rated Sales</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(zeroRatedSales)"></td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500">VAT-Exempt / Non-VAT Sales</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(exemptSales)"></td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500"
                                        x-text="taxOption === 0 ? 'VAT (not computed)' : (taxOption === 1 ? 'VAT (included)' : 'VAT (added)')">
                                    </td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(vatAmount)"></td>
                                </tr>
                                <tr class="font-bold text-gray-900">
                                    <td class="py-2">Total Amount Due</td>
                                    <td class="py-2 text-right tabular-nums" x-text="'₱' + fmt(total)"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                        <strong>Journal preview (on approval):</strong>

                        {{-- Toggle OFF or no discount → same as before --}}
                        <template x-if="!showDiscountLine">
                            <span>
                                Dr Accounts Receivable <span x-text="fmt(total)"></span> &nbsp;·&nbsp;
                                Cr Revenue <span x-text="fmt(vatableSales + zeroRatedSales + exemptSales)"></span>
                                &nbsp;·&nbsp; Cr Output VAT <span x-text="fmt(vatAmount)"></span>
                            </span>
                        </template>

                        {{-- Toggle ON with a discount → show the gross method --}}
                        <template x-if="showDiscountLine">
                            <span>
                                Dr Accounts Receivable <span x-text="fmt(total)"></span> &nbsp;·&nbsp;
                                Dr Sales Discount <span x-text="fmt(discountTotal)"></span> &nbsp;·&nbsp;
                                Cr Revenue <span x-text="fmt(grossRevenue)"></span>
                                &nbsp;·&nbsp;
                                Cr Output VAT <span x-text="fmt(vatAmount)"></span>
                            </span>
                        </template>

                        {{-- Toggle ON but no account configured → warn --}}
                        <template x-if="recordDiscountInGl && discountTotal > 0 && !hasDiscountAccount">
                            <div class="mt-2 rounded border border-red-300 bg-red-50 px-2 py-1 text-red-700">
                                <strong>Discount account missing.</strong> Set
                                <code>default_sales_discount_account_id</code> on the customer or in AR Settings.
                                Posting will be blocked until this is fixed.
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        const makeKey = () =>
            (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') ?
            crypto.randomUUID() :
            'k_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2);

        function invoiceForm(data) {
            const pad = n => String(n).padStart(2, '0');
            const r2 = n => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

            return {
                ...data,

                init() {
                    this.lines = this.lines.map(l => ({
                        ...l,
                        _key: l._key || makeKey(),
                        _itemPicked: !!(l.item_id && l.item_id !== ''),
                    }));
                    if (!this.lines.length) this.addLine();
                },

                customer() {
                    return this.customers.find(c => String(c.id) === String(this.customerId)) || null;
                },
                item(line) {
                    return this.items.find(i => String(i.id) === String(line.item_id)) || null;
                },

                lineHasVat() {
                    return this.lines.some(l => this.cls(l) === 'vatable' && this.taxAmount(l) > 0);
                },

                lineDefaultVat(line) {
                    const c = this.customer();
                    if (c && c.vat_class !== 'vatable') return c.vat_class;
                    const it = line ? this.item(line) : null;
                    if (it && it.vat && this.vatOptions.some(o => o.value === it.vat)) return it.vat;
                    const out = this.vatOptions.find(o => /output/i.test(o.label));
                    const opt = out || this.vatOptions[0];
                    return opt ? opt.value : 'exempt';
                },

                onCustomerChange() {
                    const c = this.customer();
                    if (!c) return;
                    if (c.term_id) this.termId = String(c.term_id);
                    if (c.currency_id) this.currencyId = String(c.currency_id);
                    this.recomputeDue();
                    this.lines.forEach(l => l.vat = this.lineDefaultVat(l));
                },

                recomputeDue() {
                    const t = this.terms.find(x => String(x.id) === String(this.termId));
                    if (!t || !this.invoiceDate) return;
                    const d = new Date(this.invoiceDate + 'T00:00:00');
                    d.setDate(d.getDate() + Number(t.days));
                    this.dueDate = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
                },

                addLine() {
                    this.lines.push({
                        _key: makeKey(),
                        _itemPicked: false,
                        item_id: '',
                        description: '',
                        gl_account_id: '',
                        quantity: 1,
                        unit_price: 0,
                        discount_amount: 0,
                        vat: this.lineDefaultVat(null),
                        uom_id: '',
                    });
                },
                removeLine(i) {
                    if (this.lines.length > 1) this.lines.splice(i, 1);
                },

                onItemChange(line) {
                    const it = this.item(line);
                    if (!it) return;

                    const firstPick = !line._itemPicked;
                    line._itemPicked = true;

                    line.description = it.description || it.name;

                    if (firstPick || it.gl_account_id) {
                        line.gl_account_id = it.gl_account_id ? String(it.gl_account_id) : line.gl_account_id;
                    }
                    if (firstPick || it.uom_id) {
                        line.uom_id = it.uom_id ? String(it.uom_id) : line.uom_id;
                    }
                    if (firstPick || (it.unit_price !== null && it.unit_price !== undefined && it.unit_price !== '')) {
                        line.unit_price = Number(it.unit_price) || 0;
                    }
                    if (firstPick || (it.vat && this.vatOptions.some(o => o.value === it.vat))) {
                        line.vat = (it.vat && this.vatOptions.some(o => o.value === it.vat)) ?
                            it.vat :
                            this.lineDefaultVat(line);
                    }
                },

                uomChoices(line) {
                    const it = this.item(line);
                    return it ? this.uoms.filter(u => u.root === it.root) : this.uoms;
                },
                onUomChange(line, id) {
                    const prev = this.uoms.find(u => String(u.id) === String(line.uom_id));
                    const next = this.uoms.find(u => String(u.id) === String(id));
                    line.uom_id = String(id);
                    if (prev && next && line.item_id) {
                        line.unit_price = Math.round(line.unit_price * next.rf / prev.rf * 1e6) / 1e6;
                    }
                },

                // --- figures: kept identical to server-side writeLine() ---
                taxable(l) {
                    return r2((Number(l.quantity) || 0) * (Number(l.unit_price) || 0) - (Number(l.discount_amount) || 0));
                },

                taxBucket(code) {
                    if (!code) return null;
                    const c = String(code).toUpperCase();
                    if (c === 'ZERO-RATED' || c === 'ZERO_RATED') return 'zero_rated';
                    if (c === 'VAT-EXEMPT' || c === 'EXEMPT') return 'exempt';
                    if (c === 'NON-VAT' || c === 'NON_VAT') return 'non_vat';
                    return 'vatable';
                },

                taxAmount(l) {
                    if (this.cls(l) !== 'vatable') return 0;
                    if (this.taxOption === 0) return 0;
                    const o = this.vatOptions.find(v => v.value === l.vat);
                    if (!o) return 0;
                    if (o.type === 'FixedAmount') {
                        return this.taxOption === 2 ? r2(o.fixed) : 0;
                    }
                    const rate = Number(o.rate) || 0;
                    if (this.taxOption === 1) {
                        return r2(this.taxable(l) * rate / (100 + rate));
                    }
                    return r2(this.taxable(l) * rate / 100);
                },

                /**
                 * For inclusive mode the line total stays at the taxable base
                 * (VAT is already in the price). Everything else adds the tax.
                 */
                lineTotal(l) {
                    if (this.taxOption === 1) return this.taxable(l);
                    return r2(this.taxable(l) + this.taxAmount(l));
                },

                cls(l) {
                    if (!l.vat) return null;

                    if (l.vat.startsWith('tax:')) {
                        const o = this.vatOptions.find(v => v.value === l.vat);
                        if (!o) return 'vatable'; // unknown tax id — treat as vatable
                        // If the controller doesn't yet emit `code`, fall back to
                        // the label prefix. Both work once `code` is wired up.
                        const code = o.code || (o.label || '').split('—')[0].trim();
                        return this.taxBucket(code);
                    }

                    // Literal legacy string
                    return l.vat;
                },
                sum(fn) {
                    return r2(this.lines.reduce((s, l) => s + fn(l), 0));
                },
                get subtotal() {
                    return this.sum(l => (Number(l.quantity) || 0) * (Number(l.unit_price) || 0));
                },
                get discountTotal() {
                    return this.sum(l => Number(l.discount_amount) || 0);
                },
                get vatableSales() {
                    return this.sum(l => this.cls(l) === 'vatable' ? this.taxable(l) : 0);
                },
                get zeroRatedSales() {
                    return this.sum(l => this.cls(l) === 'zero_rated' ? this.taxable(l) : 0);
                },
                get exemptSales() {
                    return this.sum(l => ['exempt', 'non_vat'].includes(this.cls(l)) ? this.taxable(l) : 0);
                },
                get vatAmount() {
                    return this.sum(l => this.taxAmount(l));
                },
                get total() {
                    return this.sum(l => this.lineTotal(l));
                },
                get resolvedDiscountAccountId() {
                    const c = this.customer();
                    return (c && c.default_sales_discount_account_id) ?
                        c.default_sales_discount_account_id :
                        (this.defaultDiscountAccountId || null);
                },

                get hasAnyDiscount() {
                    return this.discountTotal > 0;
                },

                get showDiscountLine() {
                    return this.recordDiscountInGl && this.hasAnyDiscount;
                },

                get hasDiscountAccount() {
                    return this.resolvedDiscountAccountId !== null;
                },

                get grossRevenue() {
                    const grossTaxable = r2(
                        this.vatableSales + this.zeroRatedSales + this.exemptSales + this.discountTotal
                    );
                    return this.taxOption === 1 ?
                        r2(grossTaxable - this.vatAmount) :
                        grossTaxable;
                },
                get creditWarning() {
                    const c = this.customer();
                    if (!c || !(c.credit_limit > 0)) return '';
                    return (c.open_balance + this.total) > c.credit_limit ?
                        'Credit limit exceeded: limit ' + this.fmt(c.credit_limit) + ', outstanding ' +
                        this.fmt(c.open_balance) + '. Submission will be blocked.' :
                        '';
                },
                fmt(n) {
                    return new Intl.NumberFormat('en-PH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(n || 0);
                },
            };
        }
    </script>
@endsection
