@extends('dashboard')

@section('title', 'Sales Invoice — ' . $invoice->displayNo())

@section('content')
    @php
        $badge = $invoice->statusBadgeClass();
        $payBadge = $invoice->paymentStatusBadgeClass();
        $isDraft = $invoice->approval_status === '0';
        $isReturned = $invoice->approval_status === '4';
        $isApproved = $invoice->approval_status === '2';
        $isRejected = $invoice->approval_status === '3';
        $isVoided = (bool) $invoice->voided_at;
    @endphp

    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                {{ session('error') }}</div>
        @endif

        @if ($returnRemark)
            <div class="mt-3 mb-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800">
                <strong>Returned by approver:</strong> {{ $returnRemark->remarks }}
                <span class="text-xs text-orange-600">— {{ $returnRemark->acted_at?->format('M d, Y g:i A') }}</span>
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold text-white">{{ $invoice->displayNo() }}</h1>
                            <span class="inline-flex rounded-full {{ $badge }} px-3 py-1 text-xs font-medium">
                                {{ $invoice->statusLabel() }}
                            </span>
                            @if ($isApproved)
                                <span class="inline-flex rounded-full {{ $payBadge }} px-3 py-1 text-xs font-medium">
                                    {{ $invoice->paymentStatusLabel() }}
                                </span>
                            @endif
                            @if ($isVoided)
                                <span
                                    class="inline-flex rounded-full bg-red-100 text-red-700 px-3 py-1 text-xs font-medium">
                                    Voided {{ $invoice->voided_at->format('M d, Y') }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-blue-100">
                            {{ $invoice->invoice_date->format('M d, Y') }}
                            @if ($invoice->due_date)
                                · due {{ $invoice->due_date->format('M d, Y') }}
                            @endif
                            @if ($invoice->customer_po_no)
                                · PO {{ $invoice->customer_po_no }}
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('ar.inv') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                            Back
                        </a>
                        @if ($canEdit)
                            <a href="{{ route('ar.invoices.edit', $invoice) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 hover:bg-white">
                                Edit
                            </a>
                        @endif
                        @if ($canDelete)
                            <form method="POST" action="{{ route('ar.invoices.destroy', $invoice) }}"
                                onsubmit="return confirm('Delete draft {{ $invoice->displayNo() }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-500/90 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-600">
                                    Delete
                                </button>
                            </form>
                        @endif
                        @if ($isDraft || $isReturned)
                            @if ($hasWorkflow)
                                <form method="POST" action="{{ route('ar.invoices.submit', $invoice) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">
                                        Submit for Approval
                                    </button>
                                </form>
                            @else
                                <button type="button" disabled title="An approval workflow must be configured first"
                                    class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 opacity-50 cursor-not-allowed shadow-lg">
                                    Submit for Approval
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="px-8 py-6">
                @if ($isRejected)
                    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-3 text-xs text-red-800">
                        This invoice was rejected. It cannot be edited; create a new draft if the customer still needs
                        to be billed.
                    </div>
                @endif

                {{-- Bill-to / meta grid --}}
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Bill To</div>
                        <div class="mt-2 text-sm font-medium text-gray-900">{{ $billTo['name'] }}</div>
                        <div class="mt-1 text-xs text-gray-600">TIN: {{ $billTo['tin'] }}</div>
                        @if ($billTo['address'])
                            <div class="mt-1 text-xs text-gray-600">{{ $billTo['address'] }}</div>
                        @endif
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Terms</div>
                        <div class="mt-2 text-sm text-gray-900">
                            {{ $invoice->term->name ?? '—' }}
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="text-xs font-semibold text-gray-500 uppercase">Tax Mode</div>
                            <div class="mt-2 text-sm text-gray-900">
                                @switch((int) $invoice->tax_option)
                                    @case(1)
                                        Tax Inclusive (VAT in price)
                                    @break

                                    @case(2)
                                        Tax Exclusive (VAT added)
                                    @break

                                    @default
                                        No Tax
                                @endswitch
                            </div>
                            @if ((int) $invoice->tax_option === 1)
                                <div class="mt-1 text-xs text-gray-600">VAT amount shown is embedded in the line totals.
                                </div>
                            @elseif ((int) $invoice->tax_option === 2)
                                <div class="mt-1 text-xs text-gray-600">VAT amount shown is added to the line totals.</div>
                            @else
                                <div class="mt-1 text-xs text-gray-600">No VAT has been computed on this invoice.</div>
                            @endif
                        </div>
                        <div class="mt-1 text-xs text-gray-600">
                            Due: {{ $invoice->due_date?->format('M d, Y') ?? '—' }}
                        </div>
                        <div class="mt-2 text-xs text-gray-600">
                            Currency: {{ $invoice->currency->code ?? 'PHP' }}
                            @if ($invoice->exchange_rate && (float) $invoice->exchange_rate !== 1.0)
                                × {{ number_format((float) $invoice->exchange_rate, 6) }}
                            @endif
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Amounts</div>
                        <div class="mt-2 flex justify-between text-xs text-gray-700">
                            <span>Total</span>
                            <span
                                class="tabular-nums font-medium">{{ number_format((float) $invoice->total_amount, 2) }}</span>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-700">
                            <span>Paid</span>
                            <span class="tabular-nums">{{ number_format((float) $invoice->amount_paid, 2) }}</span>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-700">
                            <span>CWT applied</span>
                            <span class="tabular-nums">{{ number_format((float) $invoice->cwt_applied, 2) }}</span>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-700">
                            <span>Credit applied</span>
                            <span class="tabular-nums">{{ number_format((float) $invoice->credit_applied, 2) }}</span>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-700">
                            <span>Debit memos</span>
                            <span class="tabular-nums">{{ number_format((float) $invoice->debit_applied, 2) }}</span>
                        </div>
                        <div
                            class="mt-2 flex justify-between border-t border-gray-200 pt-2 text-sm font-bold text-gray-900">
                            <span>Balance Due</span>
                            <span class="tabular-nums">{{ number_format((float) $invoice->amount_due, 2) }}</span>
                        </div>

                    </div>
                </div>

                {{-- Billing period / remarks --}}
                @if ($invoice->billing_period_from || $invoice->billing_period_to || $invoice->remarks)
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3 text-xs">
                        @if ($invoice->billing_period_from || $invoice->billing_period_to)
                            <div>
                                <div class="text-gray-500">Billing Period</div>
                                <div class="font-medium text-gray-800">
                                    {{ $invoice->billing_period_from?->format('M d, Y') ?? '—' }}
                                    →
                                    {{ $invoice->billing_period_to?->format('M d, Y') ?? '—' }}
                                </div>
                            </div>
                        @endif
                        @if ($invoice->remarks)
                            <div class="md:col-span-2">
                                <div class="text-gray-500">Remarks</div>
                                <div class="text-gray-800">{{ $invoice->remarks }}</div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Lines --}}
                <h3 class="mt-6 mb-3 text-sm font-semibold text-gray-700">Line Items</h3>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-[1000px] w-full text-xs">
                        <thead class="bg-gray-50 uppercase text-gray-500">
                            <tr>
                                <th class="px-2 py-2 text-left w-6">#</th>
                                <th class="px-2 py-2 text-left">Description</th>
                                <th class="px-2 py-2 text-left w-52">Revenue Account</th>
                                <th class="px-2 py-2 text-right w-20">Qty</th>
                                <th class="px-2 py-2 text-left w-20">UOM</th>
                                <th class="px-2 py-2 text-right w-28">Unit Price</th>
                                <th class="px-2 py-2 text-right w-24">Discount</th>
                                <th class="px-2 py-2 text-left w-28">VAT</th>
                                <th class="px-2 py-2 text-right w-28">Taxable</th>
                                <th class="px-2 py-2 text-right w-24">Tax</th>
                                <th class="px-2 py-2 text-right w-28">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->lines as $line)
                                <tr class="border-t border-gray-100 align-top">
                                    <td class="px-2 py-2 text-gray-400">{{ $line->line_no }}</td>
                                    <td class="px-2 py-2">
                                        <div class="text-gray-800">{{ $line->description }}</div>
                                        @if ($line->item_id && $line->item)
                                            <div class="text-[10px] text-gray-400">{{ $line->item->code }} —
                                                {{ $line->item->name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-gray-700">
                                        {{ $line->glAccount->getFormattedAccountCodeAttribute() ?? '—' }}
                                        @if ($line->glAccount)
                                            <div class="text-[10px] text-gray-400">{{ $line->glAccount->account_name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-right tabular-nums">{{ (float) $line->quantity }}</td>
                                    <td class="px-2 py-2 text-gray-600">{{ $line->uom->code ?? '—' }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums">
                                        {{ number_format((float) $line->unit_price, 2) }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums">
                                        {{ number_format((float) $line->discount_amount, 2) }}</td>
                                    <td class="px-2 py-2">
                                        @if ($line->vat_class === 'vatable')
                                            <span class="rounded bg-blue-50 px-2 py-0.5 text-[10px] text-blue-700">
                                                {{ $line->taxes->first()->tax->code ?? 'VAT' }}
                                            </span>
                                        @else
                                            <span class="rounded bg-gray-100 px-2 py-0.5 text-[10px] text-gray-700">
                                                {{ str_replace('_', ' ', $line->vat_class) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-right tabular-nums">
                                        {{ number_format((float) $line->taxable_amount, 2) }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums">
                                        {{ number_format((float) $line->tax_amount, 2) }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums font-medium text-gray-800">
                                        {{ number_format((float) $line->total_amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-8 text-center text-gray-500">No lines.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="mt-4 flex justify-end">
                    <table class="w-full max-w-sm text-xs">
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="py-1.5 text-gray-500">Subtotal</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $invoice->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-gray-500">Less: Discount</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $invoice->discount_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-gray-500">VATable Sales</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $invoice->vatable_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-gray-500">Zero-Rated Sales</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $invoice->zero_rated_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-gray-500">VAT-Exempt / Non-VAT Sales</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $invoice->vat_exempt_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-gray-500">Add: VAT</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $invoice->vat_amount, 2) }}</td>
                            </tr>
                            <tr class="font-bold text-gray-900">
                                <td class="py-2">Total Amount Due</td>
                                <td class="py-2 text-right tabular-nums">
                                    ₱{{ number_format((float) $invoice->total_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Journal / Applications --}}
                @if ($invoice->journal)
                    <h3 class="mt-8 mb-3 text-sm font-semibold text-gray-700">Journal Entry</h3>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Account</th>
                                    <th class="px-3 py-2 text-right w-32">Debit</th>
                                    <th class="px-3 py-2 text-right w-32">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($invoice->journal->lines as $jl)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700">
                                            {{ $jl->glAccount->getFormattedAccountCodeAttribute() ?? '—' }}
                                            @if ($jl->glAccount)
                                                <span class="text-gray-400">— {{ $jl->glAccount->account_name }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums">
                                            {{ (float) $jl->debit > 0 ? number_format((float) $jl->debit, 2) : '' }}
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums">
                                            {{ (float) $jl->credit > 0 ? number_format((float) $jl->credit, 2) : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($invoice->applications->isNotEmpty())
                    <h3 class="mt-8 mb-3 text-sm font-semibold text-gray-700">Settlements</h3>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Collection No.</th>
                                    <th class="px-3 py-2 text-left">Date</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-right">Applied</th>
                                    <th class="px-3 py-2 text-right">CWT</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($invoice->applications as $app)
                                    @php
                                        $coll = $app->collection;
                                        $voided = $coll && $coll->voided_at;
                                        $status = $coll?->status ?? '—';
                                    @endphp
                                    <tr class="{{ $voided ? 'opacity-60' : '' }}">
                                        <td class="px-3 py-2">
                                            @if ($coll)
                                                <a href="{{ route('ar.collections.show', $coll) }}"
                                                    class="text-blue-600 hover:underline {{ $voided ? 'line-through' : '' }}">
                                                    {{ $coll->collection_no }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">#{{ $app->collection_id }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">
                                            {{ $coll?->collection_date?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            @if ($voided)
                                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] text-red-700"
                                                    title="{{ $coll->void_reason }}">Voided</span>
                                            @elseif ($status === 'posted')
                                                <span
                                                    class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] text-green-700">Posted</span>
                                            @elseif ($status === 'bounced')
                                                <span
                                                    class="rounded-full bg-orange-100 px-2 py-0.5 text-[10px] text-orange-700">Bounced</span>
                                            @else
                                                <span
                                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-600">{{ ucfirst($status) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums {{ $voided ? 'line-through' : '' }}">
                                            {{ number_format((float) $app->amount_applied, 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums {{ $voided ? 'line-through' : '' }}">
                                            {{ number_format((float) $app->cwt_applied, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-gray-200 bg-gray-50 font-medium text-gray-800">
                                @php
                                    $liveApps = $invoice->applications->filter(fn($a) => !$a->collection?->voided_at);
                                @endphp
                                <tr>
                                    <td colspan="3" class="px-3 py-2 text-right text-gray-500">Total (live)</td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        {{ number_format((float) $liveApps->sum('amount_applied'), 2) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        {{ number_format((float) $liveApps->sum('cwt_applied'), 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                {{-- Credit / Debit Memos --}}
                @if ($invoice->debitMemos->isNotEmpty() || $invoice->creditMemos->isNotEmpty())
                    <h3 class="mt-8 mb-3 text-sm font-semibold text-gray-700">Related Memos</h3>

                    @php
                        $showMemo = function ($memo, string $type) use ($invoice) {
                            $isCredit = $type === 'credit';
                            $label = $isCredit ? 'Credit Memo' : 'Debit Memo';
                            $route = $isCredit ? 'ar.credit-memos.show' : 'ar.debit-memos.show';
                            $noCol = $isCredit ? 'credit_memo_no' : 'debit_memo_no';
                            $dateCol = $isCredit ? 'credit_date' : 'debit_date';
                            $posted = $memo->approval_status === '2' && !$memo->voided_at;
                            $voided = (bool) $memo->voided_at;
                            $pending = in_array($memo->approval_status, ['1', '4'], true);
                            $draft = $memo->approval_status === '0';
                            $rejected = $memo->approval_status === '3';

                            // The amount that hit this invoice:
                            //   credit memo  -> reduce credit_applied (positive)
                            //   debit  memo  -> increase debit_applied (positive)
                            $applied = (float) $memo->amount_applied;

                            $badge = match (true) {
                                $voided => 'bg-red-100 text-red-700',
                                $posted => 'bg-green-100 text-green-700',
                                $pending => 'bg-yellow-100 text-yellow-700',
                                $rejected => 'bg-red-100 text-red-700',
                                $draft => 'bg-gray-100 text-gray-600',
                                default => 'bg-gray-100 text-gray-600',
                            };
                            $badgeText = $voided
                                ? 'Voided'
                                : ($posted
                                    ? 'Posted'
                                    : ($pending
                                        ? 'Pending Approval'
                                        : ($rejected
                                            ? 'Rejected'
                                            : ($draft
                                                ? 'Draft'
                                                : ucfirst((string) $memo->approval_status)))));

                            return [
                                'label' => $label,
                                'route' => $route,
                                'noCol' => $noCol,
                                'dateCol' => $dateCol,
                                'memo' => $memo,
                                'posted' => $posted,
                                'voided' => $voided,
                                'applied' => $applied,
                                'badge' => $badge,
                                'badgeText' => $badgeText,
                            ];
                        };
                    @endphp

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Type</th>
                                    <th class="px-3 py-2 text-left">No.</th>
                                    <th class="px-3 py-2 text-left">Date</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-right">Memo Total</th>
                                    <th class="px-3 py-2 text-right">Applied to this Invoice</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($invoice->debitMemos as $memo)
                                    @php $row = $showMemo($memo, 'debit'); @endphp
                                    <tr class="{{ $row['voided'] ? 'opacity-60' : '' }}">
                                        <td class="px-3 py-2">
                                            <span
                                                class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] text-blue-700">Debit</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route($row['route'], $memo) }}"
                                                class="text-blue-600 hover:underline {{ $row['voided'] ? 'line-through' : '' }}">
                                                {{ $memo->{$row['noCol']} ?: $memo->displayNo() }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">
                                            {{ $memo->{$row['dateCol']}?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="rounded-full {{ $row['badge'] }} px-2 py-0.5 text-[10px]"
                                                @if ($row['voided'] && $memo->void_reason) title="{{ $memo->void_reason }}" @endif>
                                                {{ $row['badgeText'] }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-3 py-2 text-right tabular-nums {{ $row['voided'] ? 'line-through' : '' }}">
                                            +{{ number_format((float) $memo->total_amount, 2) }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-right tabular-nums font-medium {{ $row['voided'] ? 'line-through' : '' }}">
                                            {{ $row['posted'] ? '+' . number_format($row['applied'], 2) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach

                                @foreach ($invoice->creditMemos as $memo)
                                    @php $row = $showMemo($memo, 'credit'); @endphp
                                    <tr class="{{ $row['voided'] ? 'opacity-60' : '' }}">
                                        <td class="px-3 py-2">
                                            <span
                                                class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] text-amber-700">Credit</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route($row['route'], $memo) }}"
                                                class="text-blue-600 hover:underline {{ $row['voided'] ? 'line-through' : '' }}">
                                                {{ $memo->{$row['noCol']} ?: $memo->displayNo() }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">
                                            {{ $memo->{$row['dateCol']}?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="rounded-full {{ $row['badge'] }} px-2 py-0.5 text-[10px]"
                                                @if ($row['voided'] && $memo->void_reason) title="{{ $memo->void_reason }}" @endif>
                                                {{ $row['badgeText'] }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-3 py-2 text-right tabular-nums {{ $row['voided'] ? 'line-through' : '' }}">
                                            -{{ number_format((float) $memo->total_amount, 2) }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-right tabular-nums font-medium {{ $row['voided'] ? 'line-through' : '' }}">
                                            {{ $row['posted'] ? '-' . number_format($row['applied'], 2) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @php
                                $postedDebit = $invoice->debitMemos->filter(
                                    fn($m) => $m->approval_status === '2' && !$m->voided_at,
                                );
                                $postedCredit = $invoice->creditMemos->filter(
                                    fn($m) => $m->approval_status === '2' && !$m->voided_at,
                                );
                            @endphp
                            <tfoot class="border-t border-gray-200 bg-gray-50 font-medium text-gray-800">
                                <tr>
                                    <td colspan="4" class="px-3 py-2 text-right text-gray-500">Net effect on this
                                        invoice</td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        +{{ number_format($postedDebit->sum('amount_applied'), 2) }}
                                        / -{{ number_format($postedCredit->sum('amount_applied'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                {{-- Approval history --}}
                @if ($tx)
                    <h3 class="mt-8 mb-3 text-sm font-semibold text-gray-700">Approval History</h3>
                    <ul class="space-y-2 text-xs">
                        @forelse ($tx->histories as $h)
                            <li class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <span class="font-medium text-gray-800">{{ ucfirst($h->action) }}</span>
                                by
                                {{ $h->actor->last_name . ', ' . $h->actor->first_name . ' ' . $h->actor->middle_name ?? 'System' }}
                                @if ($h->remarks)
                                    — {{ $h->remarks }}
                                @endif
                                <span class="text-gray-500">— {{ $h->acted_at?->format('M d, Y g:i A') }}</span>
                            </li>
                        @empty
                            <li class="text-gray-500">No history yet.</li>
                        @endforelse
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
