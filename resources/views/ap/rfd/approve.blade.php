@extends('dashboard')

@section('title', 'Review Request for Disbursement')

@section('content')
    <div class="mx-auto max-w-6xl" x-data="rfdApproval()">

        @if ($errors->any())
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-xs text-red-700">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}
                        </h1>
                        <p class="mt-1 text-sm text-indigo-100">
                            Step: {{ $step->step_name }}
                            @if ($step->is_final_approval)
                                <span class="ml-2 rounded bg-white/20 px-2 py-0.5 text-xs font-medium">Final Approval</span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('approvals.index') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                        Back to Inbox
                    </a>
                </div>
            </div>

            <!-- Read-only header summary (vendor is per-line now, shown in the table below) -->
            <div class="px-8 py-6 border-b border-gray-200 grid grid-cols-2 gap-3 md:grid-cols-4 text-xs">
                <div>
                    <div class="text-gray-500">Request Date</div>
                    <div class="font-medium text-gray-800">{{ $rfd->request_date?->format('M d, Y') }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Term</div>
                    <div class="font-medium text-gray-800">{{ $rfd->term->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Payment Method</div>
                    <div class="font-medium text-gray-800">{{ $rfd->paymentMethod->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Currency</div>
                    <div class="font-medium text-gray-800">{{ $rfd->currency->code ?? '—' }}</div>
                </div>
                @if ($rfd->remarks)
                    <div class="col-span-2 md:col-span-4">
                        <div class="text-gray-500">Remarks</div>
                        <div class="font-medium text-gray-800">{{ $rfd->remarks }}</div>
                    </div>
                @endif
            </div>

            <!-- Attachments -->
            <div class="px-8 py-6 border-b border-gray-200 grid grid-cols-2 gap-3 md:grid-cols-4 text-xs">
                <div>
                    <div class="text-gray-500">Attachments</div>
                    <div class="font-medium text-gray-800">
                        @if ($rfd->attachments->isEmpty())
                            <span class="text-gray-400">No attachments</span>
                        @else
                            <ul class="space-y-1">
                                @foreach ($rfd->attachments as $attachment)
                                    <li>
                                        <a href="{{ route('ap.rfd.download-attachment', $attachment->id) }}"
                                            class="text-blue-600 hover:underline">
                                            {{ $attachment->description }} - {{ $attachment->file_name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Permission banner -->
            <div class="px-8 pt-4 flex flex-wrap gap-2 text-xs">
                @if ($step->can_edit_chart_of_account)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit GL account</span>
                @endif
                @if ($step->can_edit_tax)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can add/modify tax</span>
                @endif
                @if ($step->can_edit_amount)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit amounts</span>
                @endif
                @if (!$step->can_edit_chart_of_account && !$step->can_edit_tax && !$step->can_edit_amount)
                    <span class="rounded-full bg-gray-50 px-2.5 py-1 text-gray-500">View only — no line edits at this
                        step</span>
                @endif
            </div>

            <!-- Line items -->
            <div class="px-8 py-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Line Items</h2>

                <form method="POST" action="{{ route('approvals.approve', $transaction->id) }}" id="approve-form">
                    @csrf
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">#</th>
                                    <th class="px-3 py-2 text-left">Vendor</th>
                                    <th class="px-3 py-2 text-left">Description</th>
                                    <th class="px-3 py-2 text-left">GL Account</th>
                                    <th class="px-3 py-2 text-right">Qty</th>
                                    <th class="px-3 py-2 text-right">Unit Price</th>
                                    <th class="px-3 py-2 text-right">Discount</th>
                                    <th class="px-3 py-2 text-left">Taxes</th>
                                    <th class="px-3 py-2 text-right">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rfd->details as $detail)
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-3 py-2 text-gray-400">{{ $detail->line_no }}</td>

                                        <!-- Vendor is set at submission time; not editable during approval -->
                                        <td class="px-3 py-2 text-gray-800">{{ $detail->vendor->name ?? '—' }}</td>

                                        <td class="px-3 py-2 text-gray-800">{{ $detail->description }}</td>

                                        <!-- GL account: editable only if step allows -->
                                        <td class="px-3 py-2">
                                            @if ($step->can_edit_chart_of_account)
                                                <select name="lines[{{ $detail->id }}][gl_account_id]"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                                    <option value="">Select account&hellip;</option>
                                                    @foreach ($glAccounts as $account)
                                                        <option value="{{ $account->id }}" @selected($detail->gl_account_id == $account->id)>
                                                            {{ $account->account_code }} &mdash;
                                                            {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="text-gray-700">
                                                    {{ $detail->glAccount->account_code ?? '—' }}
                                                    {{ $detail->glAccount ? '— ' . $detail->glAccount->account_name : '' }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Qty is fixed at submission time -->
                                        <td class="px-3 py-2 text-right">
                                            {{ number_format($detail->quantity, 2) }}
                                        </td>

                                        <!-- Unit price / discount: editable only if step allows amount edits -->
                                        <td class="px-3 py-2 text-right">
                                            @if ($step->can_edit_amount)
                                                <input type="number" step="0.01"
                                                    name="lines[{{ $detail->id }}][unit_price]"
                                                    x-model.number="lines[{{ $loop->index }}].unit_price"
                                                    @input="recalcLine(lines[{{ $loop->index }}])"
                                                    class="w-24 rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                {{ number_format($detail->unit_price, 2) }}
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            @if ($step->can_edit_amount)
                                                <input type="number" step="0.01"
                                                    name="lines[{{ $detail->id }}][discount_amount]"
                                                    x-model.number="lines[{{ $loop->index }}].discount_amount"
                                                    @input="recalcLine(lines[{{ $loop->index }}])"
                                                    class="w-24 rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                {{ number_format($detail->discount_amount, 2) }}
                                            @endif
                                        </td>

                                        <!-- Taxes: editable only if step allows tax edits -->
                                        <td class="px-3 py-2">
                                            @if ($step->can_edit_tax)
                                                <div class="space-y-1">
                                                    <template x-for="(taxId, tIndex) in lines[{{ $loop->index }}].tax_ids"
                                                        :key="'{{ $detail->id }}-tax-' + tIndex">
                                                        <div class="flex items-center gap-1 mb-1">
                                                            <select :name="`lines[{{ $detail->id }}][tax_ids][]`"
                                                                x-model="lines[{{ $loop->index }}].tax_ids[tIndex]"
                                                                @change="recalcLine(lines[{{ $loop->index }}])"
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
                                                                @click.prevent="lines[{{ $loop->index }}].tax_ids.splice(tIndex, 1); recalcLine(lines[{{ $loop->index }}])"
                                                                class="text-red-500 hover:text-red-700">&times;</button>
                                                        </div>
                                                    </template>
                                                    <button type="button"
                                                        @click.prevent="lines[{{ $loop->index }}].tax_ids.push(''); recalcLine(lines[{{ $loop->index }}])"
                                                        class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                                        + Add tax
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-gray-700">
                                                    {{ $detail->taxes->map(fn($t) => $t->tax->code ?? '—')->implode(', ') ?: '—' }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-700"
                                            x-text="formatNumber(lines[{{ $loop->index }}].total_amount)"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 font-medium text-gray-700">
                                <tr>
                                    <td colspan="8" class="px-3 py-2 text-right text-sm">Total Due</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sm" x-text="formatNumber(totalDue)">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-900 mb-1">Remarks</label>
                        <textarea name="remarks" rows="2" placeholder="Optional notes for this approval"
                            class="w-full rounded-lg border border-gray-300 p-2.5 text-xs"></textarea>
                    </div>
                </form>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 px-8 py-5 border-t border-gray-200 bg-gray-50">
                @if ($step->can_return_to_requester)
                    <button type="button" @click="submitAs('{{ route('approvals.return', $transaction->id) }}')"
                        class="rounded-lg border border-orange-300 bg-white px-5 py-2.5 text-sm font-medium text-orange-700 hover:bg-orange-50">
                        Return to Requester
                    </button>
                @endif
                <button type="button" @click="submitAs('{{ route('approvals.reject', $transaction->id) }}')"
                    class="rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50">
                    Reject
                </button>
                <button type="submit" form="approve-form"
                    class="rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">
                    {{ $step->is_final_approval ? 'Approve & Release for Disbursement' : 'Approve & Forward' }}
                </button>
            </div>
        </div>
    </div>

    <script>
        // System settings from PHP (passed from controller via trait)
        const SYSTEM_SETTINGS = @json($systemSettings);

        // Get tax rate/fixed metadata from the rendered <option data-rate data-fixed> attributes
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

        function rfdApproval() {
            return {
                lines: [],

                init() {
                    const serverLines = @js(
    $rfd->details
        ->map(
            fn($d) => [
                'id' => $d->id,
                'quantity' => (float) $d->quantity,
                'unit_price' => (float) $d->unit_price,
                'discount_amount' => (float) $d->discount_amount,
                'tax_ids' => $d->taxes->pluck('tax_id')->map(fn($v) => (string) $v)->values(),
            ],
        )
        ->values(),
);

                    this.lines = serverLines.map(l => ({
                        id: l.id,
                        quantity: parseFloat(l.quantity ?? 0),
                        unit_price: parseFloat(l.unit_price ?? 0),
                        discount_amount: parseFloat(l.discount_amount ?? 0),
                        tax_ids: l.tax_ids ?? [],
                        taxable_amount: 0,
                        tax_amount: 0,
                        total_amount: 0,
                    }));

                    this.$nextTick(() => {
                        this.lines.forEach(l => this.recalcLine(l));
                    });
                },

                recalcLine(line) {
                    const quantity = Number(line.quantity) || 0;
                    const unitPrice = Number(line.unit_price) || 0;
                    const discount = Number(line.discount_amount) || 0;
                    const taxable = (quantity * unitPrice) - discount;

                    let tax = 0;
                    (line.tax_ids || []).forEach(taxId => {
                        if (!taxId) return;
                        const meta = getTaxRate(taxId);
                        if (meta.fixed > 0) {
                            tax += meta.fixed;
                        } else if (meta.rate > 0) {
                            tax += taxable * (meta.rate / 100);
                        }
                    });

                    line.taxable_amount = taxable;
                    line.tax_amount = tax;
                    line.total_amount = taxable + tax;
                },

                formatNumber(value) {
                    return formatCurrency(value);
                },

                get totalDue() {
                    return this.lines.reduce((sum, l) => sum + (l.total_amount || 0), 0);
                },

                submitAs(url) {
                    const form = document.getElementById('approve-form');
                    const remarks = form.querySelector('[name="remarks"]').value;
                    if (!remarks && (url.includes('/return') || url.includes('/reject'))) {
                        alert('Remarks are required to return or reject a request.');
                        return;
                    }
                    const original = form.action;
                    form.action = url;
                    form.submit();
                    form.action = original;
                },
            };
        }
    </script>
@endsection
