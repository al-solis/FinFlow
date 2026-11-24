@extends('dashboard')

@section('title', ($isEdit ? 'Edit ' : 'New ') . $cfg['label'])

@section('content')
    @php
        $isCredit = $cfg['type'] === 'credit';
        $formAction = $isEdit ? route($cfg['route'] . '.update', $memo->id) : route($cfg['route'] . '.store');
        $input = 'mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs';
        $cell = 'w-full rounded border border-gray-300 p-1.5 text-xs';
        $label = 'block text-xs font-medium text-gray-900';
        $oldLines = old('lines');
        $lines = is_array($oldLines) ? array_values($oldLines) : $initialLines;
        $dateOf = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '';
        $seriesId = old('series_id', $memo->{$cfg['series_col']} ?? '');
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
                <strong>Warning:</strong> No approval workflow is configured for {{ $cfg['plural'] }}. You can save a
                draft, but you cannot submit for approval until one is set up in the Workflow module.
            </div>
        @endif

        @if ($returnRemark)
            <div class="mt-3 mb-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800">
                <strong>Returned by approver:</strong> {{ $returnRemark->remarks }}
                <span class="text-xs text-orange-600">— {{ $returnRemark->acted_at?->format('M d, Y g:i A') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" x-data="memoForm({
            isCredit: @js($isCredit),
            customers: @js($customerOptions),
            items: @js($items),
            uoms: @js($uoms),
            invoices: @js($invoiceOptions),
            accounts: @js($accounts),
            vatOptions: @js($vatOptions),
            defaultAccountId: @js($defaultAccountId ? (string) $defaultAccountId : ''),
            linesUrl: @js($invoiceLinesUrl),
            taxOption: @js((int) old('tax_option', $memo->tax_option ?? 0)),
            lines: @js($lines),
            customerId: @js((string) old('customer_id', $memo->customer_id)),
            invoiceId: @js((string) old('invoice_id', $memo->original_invoice_id)),
            currencyId: @js((string) old('currency_id', $memo->currency_id)),
            applyToInvoice: @js((bool) old('apply_to_invoice', $applyToInvoice)),
        })">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ $isEdit ? 'Edit ' : 'New ' }}{{ $cfg['label'] }}</h1>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ $memo->{$cfg['no_col']} ?: 'Saved as a draft without a number. The BIR number is issued when you submit for approval.' }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ $isEdit ? route($cfg['route'] . '.show', $memo) : route($cfg['index_route']) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">Back</a>
                            <button type="submit" name="action" value="draft"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 hover:bg-white">
                                Save Draft
                            </button>
                            <button type="submit" name="action" value="submit" @disabled(!$hasWorkflow)
                                title="{{ $hasWorkflow ? '' : 'An approval workflow must be configured first' }}"
                                onclick="return confirm('Submit for approval? A {{ strtolower($cfg['label']) }} number will be issued.');"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg disabled:cursor-not-allowed disabled:opacity-50">
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
                                    <option :value="c.id" :selected="String(c.id) === String(customerId)"
                                        x-text="c.code + ' — ' + c.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">{{ $cfg['label'] }} Date <span class="text-red-500">*</span></label>
                            <input type="date" name="memo_date" required
                                value="{{ old('memo_date', $dateOf($memo->{$cfg['date_col']})) }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Series</label>
                            <select name="series_id" class="{{ $input }}" @disabled($memo->{$cfg['no_col']})>
                                @foreach ($seriesOptions as $s)
                                    <option value="{{ $s->id }}" @selected((string) $seriesId === (string) $s->id)>
                                        {{ $s->code }}{{ $s->is_default ? ' (default)' : '' }}</option>
                                @endforeach
                            </select>
                            @if ($seriesOptions->isEmpty())
                                <p class="mt-1 text-[11px] text-red-600">No active {{ strtolower($cfg['label']) }} series.
                                    Add one under Invoice Series before submitting.</p>
                            @endif
                        </div>

                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Reference Invoice @unless ($isCredit)<span class="text-red-500">*</span>@endunless</label>
                            <select name="invoice_id" @unless ($isCredit) required @endunless x-model="invoiceId" @change="onInvoiceChange()"
                                class="{{ $input }}">
                                <option value="">{{ $isCredit ? '— None / Standalone —' : '— Select the invoice this memo adds to —' }}</option>
                                <template x-for="i in customerInvoices" :key="i.id">
                                    <option :value="i.id" :selected="String(i.id) === String(invoiceId)"
                                        x-text="i.no + ' · ' + i.date + ' · total ' + fmt(i.total) + ' · balance ' + fmt(i.balance)">
                                    </option>
                                </template>
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400" x-show="!customerId">Select the customer first.</p>
                            <div class="mt-1 flex items-center gap-3" x-show="invoice()" x-cloak>
                                <button type="button" @click="copyLines()"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-[11px] font-medium text-gray-700 hover:bg-gray-100">
                                    Start from the invoice's lines
                                </button>
                                <span class="text-[11px] text-gray-500" x-text="loadMsg"></span>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Reason <span class="text-red-500">*</span></label>
                            @php $reasonVal = old('reason_code', $memo->reason_code ?: array_key_first($cfg['reasons'])); @endphp
                            <select name="reason_code" required class="{{ $input }}">
                                @foreach ($cfg['reasons'] as $code => $text)
                                    <option value="{{ $code }}" @selected($reasonVal === $code)>{{ $text }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">BIR requires the reason to be stated on the memo.</p>
                        </div>

                        <div>
                            <label class="{{ $label }}">Currency</label>
                            <select name="currency_id" x-model="currencyId" class="{{ $input }}">
                                <option value="">— Default —</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}">{{ $currency->code }} — {{ $currency->name }}</option>
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
                            <p class="mt-1 text-[11px] text-gray-400">Follows the original invoice when you pick it.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Remarks</label>
                            <input type="text" name="remarks" maxlength="2000"
                                value="{{ old('remarks', $memo->remarks) }}" class="{{ $input }}">
                        </div>

                        @if ($isCredit)
                            <div x-show="invoice()" x-cloak
                                class="md:col-span-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">
                                <label class="flex items-start gap-2">
                                    <input type="hidden" name="apply_to_invoice" value="0">
                                    <input type="checkbox" name="apply_to_invoice" value="1" x-model="applyToInvoice"
                                        class="mt-0.5">
                                    <span>
                                        <span class="font-medium">Apply to the original invoice's open balance</span>
                                        <span class="block text-[11px] text-blue-800">
                                            Settles up to
                                            <span x-text="fmt(Math.min(total, invoice() ? invoice().balance : 0))"></span>
                                            now. Anything above the open balance (for example an invoice that is already paid)
                                            stays as a credit to the customer. Approvers who may edit amounts can change this.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        @endif
                    </div>

                    {{-- Lines --}}
                    <div class="mt-6 mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Line Items</h3>
                        <button type="button" @click="addLine()"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">+
                            Add Line</button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-[1100px] w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-2 py-2 text-left w-6">#</th>
                                    <th class="px-2 py-2 text-left w-48">Item (optional)</th>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-left w-52">{{ $cfg['account_label'] }}</th>
                                    <th class="px-2 py-2 text-right w-20">Qty</th>
                                    <th class="px-2 py-2 text-right w-28">Unit Price</th>
                                    <th class="px-2 py-2 text-right w-24">Discount</th>
                                    <th class="px-2 py-2 text-left w-44">VAT</th>
                                    <th class="px-2 py-2 text-right w-28">Line Total</th>
                                    <th class="px-2 py-2 w-6"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, index) in lines" :key="line._key">
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-2 py-2 text-gray-400" x-text="index + 1"></td>
                                        <td class="px-2 py-2">
                                            <select :name="`lines[${index}][item_id]`" x-model="line.item_id"
                                                @change="onItemChange(line)" class="{{ $cell }}">
                                                <option value="">— Free text —</option>
                                                <template x-for="it in items" :key="it.id">
                                                    <option :value="it.id" x-text="it.code + ' — ' + it.name"
                                                        :selected="String(it.id) === String(line.item_id)"></option>
                                                </template>
                                            </select>
                                            <input type="hidden" :name="`lines[${index}][uom_id]`" :value="line.uom_id || ''">
                                            <div class="mt-1 text-[10px] text-gray-400" x-show="line.uom_id"
                                                x-text="'UOM: ' + (uoms[line.uom_id] || '')"></div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" :name="`lines[${index}][description]`"
                                                x-model="line.description" required maxlength="1000"
                                                class="{{ $cell }}">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select :name="`lines[${index}][gl_account_id]`" required
                                                @change="line.gl_account_id = $event.target.value" class="{{ $cell }}">
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
                                            <input type="number" step="0.000001" min="0"
                                                :name="`lines[${index}][unit_price]`" x-model.number="line.unit_price"
                                                required class="{{ $cell }} text-right">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" step="0.01" min="0"
                                                :name="`lines[${index}][discount_amount]`"
                                                x-model.number="line.discount_amount" class="{{ $cell }} text-right">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select :name="`lines[${index}][vat]`" required
                                                @change="line.vat = $event.target.value" class="{{ $cell }}">
                                                <template x-for="o in vatOptions" :key="o.value">
                                                    <option :value="o.value" x-text="o.label"
                                                        :selected="line.vat === o.value"></option>
                                                </template>
                                            </select>
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
                    <p class="mt-2 text-[11px] text-gray-400">Use <strong>Output</strong> VAT. Withholding tax is recorded on
                        the collection, not on a memo.</p>

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
                                    <td class="py-2">Total {{ $cfg['label'] }}</td>
                                    <td class="py-2 text-right tabular-nums" x-text="'₱' + fmt(total)"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                        <strong>Journal preview (on final approval):</strong>
                        @if ($isCredit)
                            Dr Sales Returns / Revenue <span x-text="fmt(netOfVat)"></span> &nbsp;·&nbsp;
                            Dr Output VAT <span x-text="fmt(vatAmount)"></span> &nbsp;·&nbsp;
                            Cr Accounts Receivable <span x-text="fmt(total)"></span>
                        @else
                            Dr Accounts Receivable <span x-text="fmt(total)"></span> &nbsp;·&nbsp;
                            Cr Revenue <span x-text="fmt(netOfVat)"></span> &nbsp;·&nbsp;
                            Cr Output VAT <span x-text="fmt(vatAmount)"></span>
                        @endif
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

        function memoForm(data) {
            const r2 = n => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

            return {
                ...data,
                loadMsg: '',

                init() {
                    this.lines = this.lines.map(l => ({
                        ...l,
                        _key: l._key || makeKey()
                    }));
                    if (!this.lines.length) this.addLine();
                },

                customer() {
                    return this.customers.find(c => String(c.id) === String(this.customerId)) || null;
                },
                invoice() {
                    return this.invoices.find(i => String(i.id) === String(this.invoiceId)) || null;
                },
                get customerInvoices() {
                    return this.invoices.filter(i => String(i.customer_id) === String(this.customerId));
                },

                onCustomerChange() {
                    const c = this.customer();
                    if (c && c.currency_id) this.currencyId = String(c.currency_id);
                    if (!this.invoice() || String(this.invoice().customer_id) !== String(this.customerId)) {
                        this.invoiceId = '';
                    }
                },
                onInvoiceChange() {
                    const i = this.invoice();
                    if (i) this.taxOption = Number(i.tax_option);
                    this.loadMsg = '';
                },

                async copyLines() {
                    const i = this.invoice();
                    if (!i) return;
                    if (this.lines.some(l => l.description) && !confirm('Replace the current lines with the invoice lines?')) return;
                    this.loadMsg = 'Loading…';
                    try {
                        const res = await fetch(this.linesUrl.replace('__ID__', i.id), {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const json = await res.json();
                        this.taxOption = Number(json.tax_option);
                        this.lines = json.lines.map(l => ({
                            item_id: '',
                            uom_id: '',
                            ...l,
                            gl_account_id: String(
                                (this.isCredit && this.defaultAccountId) ? this.defaultAccountId : l.gl_account_id
                            ),
                            _key: makeKey(),
                        }));
                        if (!this.lines.length) this.addLine();
                        this.loadMsg = this.lines.length + ' line(s) loaded. Adjust quantities or prices to the amount being ' +
                            (this.isCredit ? 'credited.' : 'charged.');
                    } catch (e) {
                        this.loadMsg = 'Could not load the invoice lines.';
                    }
                },

                lineDefaultVat() {
                    const c = this.customer();
                    if (c && c.vat_class !== 'vatable') return c.vat_class;
                    const out = this.vatOptions.find(o => /output/i.test(o.label));
                    const opt = out || this.vatOptions[0];
                    return opt ? opt.value : 'exempt';
                },
                addLine() {
                    this.lines.push({
                        _key: makeKey(),
                        item_id: '',
                        uom_id: '',
                        description: '',
                        gl_account_id: this.defaultAccountId || '',
                        quantity: 1,
                        unit_price: 0,
                        discount_amount: 0,
                        vat: this.lineDefaultVat(),
                    });
                },
                removeLine(i) {
                    if (this.lines.length > 1) this.lines.splice(i, 1);
                },

                // Picking an item fills the line from the item's selling defaults.
                // Everything stays editable; choosing "Free text" clears only the item link.
                onItemChange(line) {
                    const it = this.items.find(x => String(x.id) === String(line.item_id));
                    if (!it) {
                        line.uom_id = '';
                        return;
                    }
                    line.description = it.description || it.name;
                    line.uom_id = it.uom_id || '';
                    if (it.gl_account_id) line.gl_account_id = String(it.gl_account_id);
                    if (it.unit_price) line.unit_price = Number(it.unit_price);
                    if (it.vat && this.vatOptions.some(o => o.value === it.vat)) line.vat = it.vat;
                },

                // --- figures: identical to the server-side writeLine() ---
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
                cls(l) {
                    if (!l.vat) return null;
                    if (l.vat.startsWith('tax:')) {
                        const o = this.vatOptions.find(v => v.value === l.vat);
                        if (!o) return 'vatable';
                        return this.taxBucket(o.code || (o.label || '').split('—')[0].trim());
                    }
                    return l.vat;
                },
                taxAmount(l) {
                    if (this.cls(l) !== 'vatable' || this.taxOption === 0) return 0;
                    const o = this.vatOptions.find(v => v.value === l.vat);
                    if (!o) return 0;
                    if (o.type === 'FixedAmount') return this.taxOption === 2 ? r2(o.fixed) : 0;
                    const rate = Number(o.rate) || 0;
                    return this.taxOption === 1 ? r2(this.taxable(l) * rate / (100 + rate)) : r2(this.taxable(l) * rate / 100);
                },
                lineTotal(l) {
                    return this.taxOption === 1 ? this.taxable(l) : r2(this.taxable(l) + this.taxAmount(l));
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
                get netOfVat() {
                    return r2(this.total - this.vatAmount);
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
