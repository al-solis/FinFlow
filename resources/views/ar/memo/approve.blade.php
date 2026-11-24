@extends('dashboard')

@section('title', 'Review ' . $cfg['label'])

@section('content')
    @php
        $isCredit = $cfg['type'] === 'credit';
        $canEditCoa = (bool) $step->can_edit_chart_of_account;
        $canEditTax = (bool) $step->can_edit_tax;
        $canEditAmount = (bool) $step->can_edit_amount;
        $isEditable = $canEditCoa || $canEditTax || $canEditAmount;
        $date = $memo->{$cfg['date_col']};
        $taxModes = [
            '0' => 'No Tax',
            '1' => 'Tax Inclusive (price includes VAT)',
            '2' => 'Tax Exclusive (add VAT to price)',
        ];
    @endphp

    <div class="mx-auto max-w-7xl"
        @if ($isEditable) x-data="approveForm({
            taxOption: @js((int) $taxOption),
            vatOptions: @js($vatOptions),
            lineData: @js($lineData),
            applied: @js($appliedAmount),
            balance: @js($invoice ? (float) $invoice->amount_due : 0),
            isCredit: @js($isCredit),
        })" @endif>

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
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-8 py-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Review {{ $cfg['label'] }}</h1>
                    <p class="mt-1 text-sm text-emerald-100">
                        {{ $memo->displayNo() }} · Step {{ $step->step_no }} — {{ $step->step_name }}
                        @if ($step->is_final_approval)
                            · Final approval will post to the GL
                        @endif
                    </p>
                </div>
                <a href="{{ route('approvals.index') }}"
                    class="rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">Back
                    to Approvals</a>
            </div>

            <div class="px-8 py-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4 text-xs">
                    <div>
                        <div class="text-gray-500">Customer</div>
                        <div class="font-medium text-gray-900">{{ $billToName }}</div>
                        <div class="text-gray-500 mt-1">TIN: <span class="text-gray-800">{{ $tin ?: '—' }}</span></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Original Invoice</div>
                        <div class="font-medium text-gray-900">{{ $invoice?->invoice_no ?? '—' }}</div>
                        @if ($invoice)
                            <div class="text-gray-500 mt-1">Total {{ number_format((float) $invoice->total_amount, 2) }} ·
                                balance {{ number_format((float) $invoice->amount_due, 2) }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-gray-500">{{ $cfg['label'] }} Date</div>
                        <div class="font-medium text-gray-900">{{ $date?->format('M d, Y') }}</div>
                        <div class="text-gray-500 mt-1">Reason: <span class="text-gray-800">{{ $reasonLabel }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">Total</div>
                        <div class="font-bold text-gray-900 text-base">
                            @if ($isEditable)
                                <span x-text="'₱' + fmt(total)">₱{{ number_format((float) $memo->total_amount, 2) }}</span>
                            @else
                                ₱{{ number_format((float) $memo->total_amount, 2) }}
                            @endif
                        </div>
                        <div class="text-gray-500 mt-1">{{ $taxModes[(string) $memo->tax_option] ?? '' }}</div>
                    </div>
                </div>

                <h3 class="mt-6 mb-3 text-sm font-semibold text-gray-700">Line Items</h3>
                <form method="POST" action="{{ route($cfg['route'] . '.approve', $transaction) }}">
                    @csrf
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-[1000px] w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-2 py-2 text-left w-6">#</th>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-left w-56">{{ $cfg['account_label'] }}</th>
                                    <th class="px-2 py-2 text-right w-20">Qty</th>
                                    <th class="px-2 py-2 text-right w-28">Unit Price</th>
                                    <th class="px-2 py-2 text-right w-24">Discount</th>
                                    <th class="px-2 py-2 text-left w-52">VAT</th>
                                    <th class="px-2 py-2 text-right w-24">Tax</th>
                                    <th class="px-2 py-2 text-right w-28">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($memo->lines as $line)
                                    @php $currentVat = $lineVatValues[$line->id] ?? ''; @endphp
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-2 py-2 text-gray-400">{{ $line->line_no }}</td>
                                        <td class="px-2 py-2">{{ $line->description }}</td>
                                        <td class="px-2 py-2">
                                            @if ($canEditCoa)
                                                <select name="lines[{{ $line->id }}][gl_account_id]"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                                    @foreach ($glAccounts as $a)
                                                        <option value="{{ $a->id }}" @selected($a->id == $line->gl_account_id)>
                                                            {{ $a->account_code }} — {{ $a->account_name }}</option>
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
                                                            {{ $opt['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="rounded bg-blue-50 px-2 py-0.5 text-[10px] text-blue-700">
                                                    {{ $line->taxes->first()->tax->code ?? strtoupper(str_replace('_', '-', (string) $line->vat_class)) }}</span>
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
                            <tfoot class="border-t border-gray-200 bg-gray-50">
                                <tr>
                                    <td colspan="7" class="px-2 py-2 text-right text-gray-500">Output VAT / Total</td>
                                    <td class="px-2 py-2 text-right tabular-nums font-medium">
                                        @if ($isEditable)
                                            <span x-text="fmt(vatTotal)"></span>
                                        @else
                                            {{ number_format((float) $memo->vat_amount, 2) }}
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-right tabular-nums font-bold">
                                        @if ($isEditable)
                                            <span x-text="fmt(total)"></span>
                                        @else
                                            {{ number_format((float) $memo->total_amount, 2) }}
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if ($isCredit)
                        <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">
                            <div class="flex flex-wrap items-center gap-4">
                                <div>
                                    <div class="font-medium">Applied to the original invoice</div>
                                    <div class="text-[11px] text-blue-800">Capped at the memo total and the invoice's open
                                        balance ({{ number_format((float) ($invoice->amount_due ?? 0), 2) }}).</div>
                                </div>
                                @if ($canEditAmount)
                                    <input type="number" step="0.01" min="0" name="applied"
                                        x-model.number="applied"
                                        class="w-40 rounded border border-gray-300 p-1.5 text-xs text-right">
                                @else
                                    <span class="font-semibold tabular-nums">{{ number_format($appliedAmount, 2) }}</span>
                                @endif
                                <div class="text-[11px] text-blue-800">
                                    Unapplied credit:
                                    @if ($isEditable)
                                        <span class="tabular-nums"
                                            x-text="fmt(Math.max(total - Math.min(applied, total), 0))"></span>
                                    @else
                                        {{ number_format(max((float) $memo->total_amount - $appliedAmount, 0), 2) }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                        <strong>Journal preview (on final approval):</strong>
                        @if ($isCredit)
                            Dr Sales Returns / Revenue
                            @if ($isEditable)
                                <span
                                    x-text="fmt(total - vatTotal)"></span>@else{{ number_format((float) $memo->total_amount - (float) $memo->vat_amount, 2) }}
                            @endif
                            &nbsp;·&nbsp; Dr Output VAT
                            @if ($isEditable)
                                <span x-text="fmt(vatTotal)"></span>@else{{ number_format((float) $memo->vat_amount, 2) }}
                            @endif
                            &nbsp;·&nbsp; Cr Accounts Receivable
                            @if ($isEditable)
                                <span x-text="fmt(total)"></span>@else{{ number_format((float) $memo->total_amount, 2) }}
                            @endif
                        @else
                            Dr Accounts Receivable
                            @if ($isEditable)
                                <span x-text="fmt(total)"></span>@else{{ number_format((float) $memo->total_amount, 2) }}
                            @endif
                            &nbsp;·&nbsp; Cr Revenue
                            @if ($isEditable)
                                <span
                                    x-text="fmt(total - vatTotal)"></span>@else{{ number_format((float) $memo->total_amount - (float) $memo->vat_amount, 2) }}
                            @endif
                            &nbsp;·&nbsp; Cr Output VAT
                            @if ($isEditable)
                                <span x-text="fmt(vatTotal)"></span>@else{{ number_format((float) $memo->vat_amount, 2) }}
                            @endif
                        @endif
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-900">Approval Remarks</label>
                        <textarea name="remarks" rows="2" maxlength="1000"
                            class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs"></textarea>
                        <p class="mt-1 text-[11px] text-gray-400">Remarks are required to return or reject.</p>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                        <button type="submit" formaction="{{ route($cfg['route'] . '.return', $transaction) }}"
                            onclick="return confirm('Return this {{ strtolower($cfg['label']) }} to the requester?');"
                            class="rounded-lg border border-orange-300 bg-orange-50 px-5 py-2.5 text-sm font-medium text-orange-800 hover:bg-orange-100">Return</button>
                        <button type="submit" formaction="{{ route($cfg['route'] . '.reject', $transaction) }}"
                            onclick="return confirm('Reject this {{ strtolower($cfg['label']) }}?');"
                            class="rounded-lg border border-red-300 bg-red-50 px-5 py-2.5 text-sm font-medium text-red-800 hover:bg-red-100">Reject</button>
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 shadow">
                            {{ $step->is_final_approval ? 'Approve & Post' : 'Approve & Forward' }}</button>
                    </div>
                </form>

                @if ($history->isNotEmpty())
                    <h3 class="mt-8 mb-3 text-sm font-semibold text-gray-700">History</h3>
                    <ul class="space-y-2 text-xs">
                        @foreach ($history as $h)
                            <li class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <span class="font-medium text-gray-800">{{ ucfirst($h->action) }}</span>
                                by {{ $h->actor->name ?? 'System' }}
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
                    ...data,

                    optionFor(vat) {
                        return this.vatOptions.find(o => o.value === vat) || null;
                    },
                    cls(vat) {
                        if (!vat) return null;
                        if (String(vat).startsWith('tax:')) {
                            const o = this.optionFor(vat);
                            if (!o) return 'vatable';
                            const c = String(o.code || (o.label || '').split('—')[0].trim()).toUpperCase();
                            if (c === 'ZERO-RATED' || c === 'ZERO_RATED') return 'zero_rated';
                            if (c === 'VAT-EXEMPT' || c === 'EXEMPT') return 'exempt';
                            if (c === 'NON-VAT' || c === 'NON_VAT') return 'non_vat';
                            return 'vatable';
                        }
                        return vat;
                    },
                    taxable(id) {
                        const l = this.lineData[id];
                        return r2((Number(l.quantity) || 0) * (Number(l.unit_price) || 0) - (Number(l.discount_amount) || 0));
                    },
                    taxAmount(id) {
                        const l = this.lineData[id];
                        if (this.cls(l.vat) !== 'vatable' || this.taxOption === 0) return 0;
                        const o = this.optionFor(l.vat);
                        if (!o) return 0;
                        if (o.type === 'FixedAmount') return this.taxOption === 2 ? r2(o.fixed) : 0;
                        const rate = Number(o.rate) || 0;
                        return this.taxOption === 1 ?
                            r2(this.taxable(id) * rate / (100 + rate)) :
                            r2(this.taxable(id) * rate / 100);
                    },
                    lineTotal(id) {
                        return this.taxOption === 1 ? this.taxable(id) : r2(this.taxable(id) + this.taxAmount(id));
                    },
                    get ids() {
                        return Object.keys(this.lineData);
                    },
                    get total() {
                        return r2(this.ids.reduce((s, id) => s + this.lineTotal(id), 0));
                    },
                    get vatTotal() {
                        return r2(this.ids.reduce((s, id) => s + this.taxAmount(id), 0));
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
