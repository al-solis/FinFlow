@extends('dashboard')

@section('title', 'Review Sales Invoice')

@section('content')
    @php
        $canEditCoa = (bool) $step->can_edit_chart_of_account;
        $canEditTax = (bool) $step->can_edit_tax;
        $canEditAmount = (bool) $step->can_edit_amount;
        $isEditable = $canEditCoa || $canEditTax || $canEditAmount;
        $recordDiscountInGl = (bool) ($arSettings->record_sales_discount_in_gl ?? false);
        $defaultDiscountAccountId = $arSettings->default_sales_discount_account_id ?? null;
        $customerDiscountAccountId = $invoice->customer->default_sales_discount_account_id ?? null;
    @endphp

    <div class="mx-auto max-w-7xl"
        @if ($isEditable) x-data="approveForm({
             taxOption:  @js((int) $taxOption),
             vatOptions: @js($vatOptions),
             lineData:   @js($lineData),
             recordDiscountInGl: @js($recordDiscountInGl),
             defaultDiscountAccountId: @js($defaultDiscountAccountId),
             customerDiscountAccountId: @js($customerDiscountAccountId),
         })" @endif>

        {{-- errors --}}
        @if (session('error'))
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}
            </div>
        @endif
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

        <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Review Sales Invoice</h1>
                    <p class="mt-1 text-sm text-blue-100">
                        {{ $invoice->displayNo() }}
                        · Step {{ $step->step_no }} — {{ $step->step_name }}
                        @if ($step->is_final_approval)
                            · Final approval will post to the GL
                        @endif
                    </p>
                </div>
                <a href="{{ route('approvals.index') }}"
                    class="rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                    Back to Approvals
                </a>
            </div>

            <div class="px-8 py-6">
                {{-- Header summary --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4 text-xs">
                    <div>
                        <div class="text-gray-500">Customer</div>
                        <div class="font-medium text-gray-900">{{ $invoice->bill_to_name }}</div>
                        <div class="text-gray-500 mt-1">TIN: <span class="text-gray-800">{{ $tin }}</span></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Invoice Date</div>
                        <div class="font-medium text-gray-900">{{ $invoice->invoice_date->format('M d, Y') }}</div>
                        <div class="text-gray-500 mt-1">Due: {{ $invoice->due_date?->format('M d, Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Currency</div>
                        <div class="font-medium text-gray-900">
                            {{ $invoice->currency->code ?? 'PHP' }} ×
                            {{ number_format((float) $invoice->exchange_rate, 6) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">Total</div>
                        <div class="font-bold text-gray-900 text-base">
                            @if ($isEditable)
                                <span
                                    x-text="'₱' + fmt(total)">₱{{ number_format((float) $invoice->total_amount, 2) }}</span>
                            @else
                                {{ number_format((float) $invoice->total_amount, 2) }}
                            @endif
                        </div>
                        @php
                            $taxOptionLabel = [
                                '0' => 'No Tax',
                                '1' => 'Tax Inclusive (price includes VAT)',
                                '2' => 'Tax Exclusive (add VAT to price)',
                            ];
                        @endphp
                        <div class="text-gray-500 mt-1">
                            {{ $taxOptionLabel[$invoice->tax_option] ?? 'Unknown Tax Mode' }}</div>
                    </div>
                </div>

                {{-- Line items --}}
                <h3 class="mt-6 mb-3 text-sm font-semibold text-gray-700">Line Items</h3>
                <form method="POST" action="{{ route('ar.invoices.approve', $transaction) }}">
                    @csrf
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-[1000px] w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-2 py-2 text-left w-6">#</th>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-left w-56">Revenue Account</th>
                                    <th class="px-2 py-2 text-right w-20">Qty</th>
                                    <th class="px-2 py-2 text-right w-28">Unit Price</th>
                                    <th class="px-2 py-2 text-right w-24">Discount</th>
                                    <th class="px-2 py-2 text-left w-56">VAT</th>
                                    <th class="px-2 py-2 text-right w-24">Tax</th>
                                    <th class="px-2 py-2 text-right w-28">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->lines as $line)
                                    @php
                                        $currentVat = $lineVatValues[$line->id] ?? '';
                                    @endphp
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-2 py-2 text-gray-400">{{ $line->line_no }}</td>
                                        <td class="px-2 py-2">{{ $line->description }}</td>
                                        <td class="px-2 py-2">
                                            @if ($canEditCoa)
                                                <select name="lines[{{ $line->id }}][gl_account_id]"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                                    @foreach ($glAccounts as $a)
                                                        <option value="{{ $a->id }}" @selected($a->id == $line->gl_account_id)>
                                                            {{ $a->account_code }} — {{ $a->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                {{ $line->glAccount->getFormattedAccountCodeAttribute() ?? '—' }} —
                                                {{ $line->glAccount->account_name ?? '' }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            @if ($canEditAmount)
                                                <input type="number" step="0.000001" min="0.000001"
                                                    name="lines[{{ $line->id }}][quantity]"
                                                    x-model.number="lineData[{{ $line->id }}].quantity"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                {{ (float) $line->quantity }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            @if ($canEditAmount)
                                                <input type="number" step="0.000001" min="0"
                                                    name="lines[{{ $line->id }}][unit_price]"
                                                    x-model.number="lineData[{{ $line->id }}].unit_price"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                {{ number_format((float) $line->unit_price, 2) }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            @if ($canEditAmount)
                                                <input type="number" step="0.01" min="0"
                                                    name="lines[{{ $line->id }}][discount_amount]"
                                                    x-model.number="lineData[{{ $line->id }}].discount_amount"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                {{ number_format((float) $line->discount_amount, 2) }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2">
                                            @if ($canEditTax)
                                                <select name="lines[{{ $line->id }}][vat]"
                                                    x-model="lineData[{{ $line->id }}].vat"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                                    @foreach ($vatOptions as $opt)
                                                        <option value="{{ $opt['value'] }}" @selected($currentVat === $opt['value'])>
                                                            {{ $opt['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                @php
                                                    $vatClass = $line->vat_class;

                                                    [$vatLabel, $vatTone] = match ($vatClass) {
                                                        'vatable' => [
                                                            $line->taxes->first()?->tax?->code ?? 'VAT',
                                                            'bg-blue-50 text-blue-700',
                                                        ],
                                                        'zero_rated' => [
                                                            'Zero-Rated',
                                                            'bg-emerald-50 text-emerald-700',
                                                        ],
                                                        'exempt' => ['VAT-Exempt', 'bg-amber-50 text-amber-700'],
                                                        'non_vat' => ['Non-VAT', 'bg-gray-100 text-gray-700'],
                                                        default => [$vatClass ?: '—', 'bg-gray-100 text-gray-700'],
                                                    };
                                                @endphp
                                                <span
                                                    class="rounded {{ $vatTone }} px-2 py-0.5 text-[10px]">{{ $vatLabel }}</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-gray-600">
                                            @if ($isEditable)
                                                <span x-text="fmt(taxAmount({{ $line->id }}))">0.00</span>
                                            @else
                                                {{ number_format((float) $line->tax_amount, 2) }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums font-medium text-gray-800">
                                            @if ($isEditable)
                                                <span x-text="fmt(lineTotal({{ $line->id }}))">0.00</span>
                                            @else
                                                {{ number_format((float) $line->total_amount, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                        <strong>Journal preview (on final approval):</strong>

                        @if ($isEditable)
                            {{-- Approver has edit rights → live Alpine preview --}}

                            {{-- Toggle OFF or no discount --}}
                            <template x-if="!showDiscountLine">
                                <span>
                                    Dr Accounts Receivable
                                    <span x-text="fmt(total)">{{ number_format((float) $invoice->total_amount, 2) }}</span>
                                    &nbsp;·&nbsp;
                                    Cr Revenue
                                    <span x-text="fmt(vatableSales + zeroRatedSales + exemptSales)">
                                        {{ number_format((float) ($invoice->vatable_sales + $invoice->zero_rated_sales + $invoice->vat_exempt_sales), 2) }}
                                    </span>
                                    &nbsp;·&nbsp;
                                    Cr Output VAT
                                    <span
                                        x-text="fmt(vatAmount)">{{ number_format((float) $invoice->vat_amount, 2) }}</span>
                                </span>
                            </template>

                            {{-- Toggle ON with a discount --}}
                            <template x-if="showDiscountLine">
                                <span>
                                    Dr Accounts Receivable
                                    <span x-text="fmt(total)"></span>
                                    &nbsp;·&nbsp;
                                    Dr Sales Discount
                                    <span x-text="fmt(discountTotal)"></span>
                                    &nbsp;·&nbsp;
                                    Cr Revenue
                                    <span x-text="fmt(grossRevenue)"></span>
                                    &nbsp;·&nbsp;
                                    Cr Output VAT
                                    <span x-text="fmt(vatAmount)"></span>
                                </span>
                            </template>

                            {{-- Toggle ON, discount exists, but no account configured --}}
                            <template x-if="recordDiscountInGl && discountTotal > 0 && !hasDiscountAccount">
                                <div class="mt-2 rounded border border-red-300 bg-red-50 px-2 py-1 text-red-700">
                                    <strong>Discount account missing.</strong> The posting will be blocked until
                                    <code>default_sales_discount_account_id</code> is set on the customer or in AR Settings.
                                </div>
                            </template>
                        @else
                            {{-- Approver cannot edit → static preview using the invoice's stored totals --}}

                            @php
                                $netRevenue =
                                    (float) $invoice->vatable_sales +
                                    (float) $invoice->zero_rated_sales +
                                    (float) $invoice->vat_exempt_sales;

                                $hasDiscount = $recordDiscountInGl && (float) $invoice->discount_amount > 0;
                                $grossRevenue = $hasDiscount
                                    ? ((int) $invoice->tax_option === 1
                                        ? $netRevenue + (float) $invoice->discount_amount - (float) $invoice->vat_amount
                                        : $netRevenue + (float) $invoice->discount_amount)
                                    : $netRevenue;
                            @endphp

                            @if ($hasDiscount)
                                Dr Accounts Receivable {{ number_format((float) $invoice->total_amount, 2) }}
                                &nbsp;·&nbsp;
                                Dr Sales Discount {{ number_format((float) $invoice->discount_amount, 2) }}
                                &nbsp;·&nbsp;
                                Cr Revenue {{ number_format($grossRevenue, 2) }}
                                &nbsp;·&nbsp;
                                Cr Output VAT {{ number_format((float) $invoice->vat_amount, 2) }}
                            @else
                                Dr Accounts Receivable {{ number_format((float) $invoice->total_amount, 2) }}
                                &nbsp;·&nbsp;
                                Cr Revenue {{ number_format($netRevenue, 2) }}
                                &nbsp;·&nbsp;
                                Cr Output VAT {{ number_format((float) $invoice->vat_amount, 2) }}
                            @endif
                        @endif
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-900">Approval Remarks</label>
                        <textarea name="remarks" rows="2" maxlength="1000"
                            class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs"></textarea>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                        <button type="submit" formaction="{{ route('ar.invoices.return', $transaction) }}"
                            onclick="return confirm('Return this invoice to the requester?');"
                            class="rounded-lg border border-orange-300 bg-orange-50 px-5 py-2.5 text-sm font-medium text-orange-800 hover:bg-orange-100">
                            Return
                        </button>
                        <button type="submit" formaction="{{ route('ar.invoices.reject', $transaction) }}"
                            onclick="return confirm('Reject this invoice?');"
                            class="rounded-lg border border-red-300 bg-red-50 px-5 py-2.5 text-sm font-medium text-red-800 hover:bg-red-100">
                            Reject
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-700 shadow">
                            {{ $step->is_final_approval ? 'Approve & Post' : 'Approve & Forward' }}
                        </button>
                    </div>
                </form>

                {{-- History --}}
                @if ($history->isNotEmpty())
                    <h3 class="mt-8 mb-3 text-sm font-semibold text-gray-700">History</h3>
                    <ul class="space-y-2 text-xs">
                        @foreach ($history as $h)
                            <li class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <span class="font-medium text-gray-800">{{ ucfirst($h->action) }}</span>
                                by
                                {{ $h->actor->last_name . ', ' . $h->actor->first_name . ' ' . $h->actor->middle_name ?? 'System' }}
                                @if ($h->remarks)
                                    — {{ $h->remarks }}
                                @endif
                                <span class="text-gray-500">— {{ $h->acted_at?->format('M d, Y g:i A') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    @if ($isEditable)
        <script>
            function approveForm(data) {
                const r2 = n => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

                return {
                    taxOption: data.taxOption,
                    vatOptions: data.vatOptions,
                    // lineData is keyed by line id → { quantity, unit_price, discount_amount, vat }
                    lineData: data.lineData,

                    // Same taxonomy as the invoice form: tax:<id> is a real tax;
                    // ZERO-RATED / VAT-EXEMPT / NON-VAT are system taxes whose code
                    // maps to a BIR bucket.
                    taxBucket(code) {
                        if (!code) return null;
                        const c = String(code).toUpperCase();
                        if (c === 'ZERO-RATED' || c === 'ZERO_RATED') return 'zero_rated';
                        if (c === 'VAT-EXEMPT' || c === 'EXEMPT') return 'exempt';
                        if (c === 'NON-VAT' || c === 'NON_VAT') return 'non_vat';
                        return 'vatable';
                    },

                    cls(lineId) {
                        const l = this.lineData[lineId];
                        if (!l || !l.vat) return null;

                        if (l.vat.startsWith('tax:')) {
                            const o = this.vatOptions.find(v => v.value === l.vat);
                            if (!o) return 'vatable';
                            const code = o.code || (o.label || '').split('—')[0].trim();
                            return this.taxBucket(code);
                        }
                        return l.vat; // legacy literal
                    },

                    taxable(lineId) {
                        const l = this.lineData[lineId];
                        if (!l) return 0;
                        return r2(
                            (Number(l.quantity) || 0) * (Number(l.unit_price) || 0) -
                            (Number(l.discount_amount) || 0)
                        );
                    },

                    taxAmount(lineId) {
                        if (this.cls(lineId) !== 'vatable') return 0;
                        if (this.taxOption === 0) return 0;
                        const l = this.lineData[lineId];
                        const o = this.vatOptions.find(v => v.value === l.vat);
                        if (!o) return 0;
                        if (o.type === 'FixedAmount') {
                            return this.taxOption === 2 ? r2(o.fixed) : 0;
                        }
                        const rate = Number(o.rate) || 0;
                        if (this.taxOption === 1) {
                            return r2(this.taxable(lineId) * rate / (100 + rate));
                        }
                        return r2(this.taxable(lineId) * rate / 100);
                    },

                    lineTotal(lineId) {
                        if (this.taxOption === 1) return this.taxable(lineId);
                        return r2(this.taxable(lineId) + this.taxAmount(lineId));
                    },

                    // Aggregates across all lines
                    sum(fn) {
                        return r2(Object.keys(this.lineData).reduce(
                            (s, id) => s + fn(id), 0
                        ));
                    },
                    get vatableSales() {
                        return this.sum(id => this.cls(id) === 'vatable' ? this.taxable(id) : 0);
                    },
                    get zeroRatedSales() {
                        return this.sum(id => this.cls(id) === 'zero_rated' ? this.taxable(id) : 0);
                    },
                    get exemptSales() {
                        return this.sum(id => ['exempt', 'non_vat'].includes(this.cls(id)) ? this.taxable(id) : 0);
                    },
                    get vatAmount() {
                        return this.sum(id => this.taxAmount(id));
                    },
                    get total() {
                        return this.sum(id => this.lineTotal(id));
                    },

                    get resolvedDiscountAccountId() {
                        return this.customerDiscountAccountId ?
                            this.customerDiscountAccountId :
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

                    get discountTotal() {
                        return this.sum(id => Number(this.lineData[id].discount_amount) || 0);
                    },

                    get grossRevenue() {
                        const grossTaxable = r2(
                            this.vatableSales + this.zeroRatedSales + this.exemptSales + this.discountTotal
                        );
                        return this.taxOption === 1 ?
                            r2(grossTaxable - this.vatAmount) :
                            grossTaxable;
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
    @endif
@endsection
