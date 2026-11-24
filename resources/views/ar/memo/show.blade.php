@extends('dashboard')

@section('title', $cfg['label'] . ' — ' . $memo->displayNo())

@section('content')
    @php
        $isCredit = $cfg['type'] === 'credit';
        $isVoided = $memo->status === 'voided' || $memo->voided_at;
        $date = $memo->{$cfg['date_col']};
        $taxModes = ['0' => 'No Tax', '1' => 'Tax Inclusive', '2' => 'Tax Exclusive'];
        $inv = $memo->originalInvoice;
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
        @if ($isVoided && $memo->void_reason)
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                <strong>Voided {{ $memo->voided_at?->format('M d, Y') }}:</strong> {{ $memo->void_reason }}
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold text-white">{{ $memo->displayNo() }}</h1>
                            <span
                                class="inline-flex rounded-full {{ $memo->statusBadgeClass() }} px-3 py-1 text-xs font-medium">{{ $memo->statusLabel() }}</span>
                        </div>
                        <p class="mt-1 text-sm text-blue-100">{{ $cfg['label'] }} · {{ $date?->format('M d, Y') }}
                            @if ($inv)
                                · against invoice {{ $inv->invoice_no }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route($cfg['index_route']) }}"
                            class="rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">Back</a>

                        @if ($canEdit)
                            <a href="{{ route($cfg['route'] . '.edit', $memo) }}"
                                class="rounded-lg bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 hover:bg-white">Edit</a>
                        @endif

                        @if ($canDelete)
                            <form method="POST" action="{{ route($cfg['route'] . '.destroy', $memo) }}"
                                onsubmit="return confirm('Delete this draft?');">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100">Delete</button>
                            </form>
                        @endif

                        @if ($canEdit)
                            @if ($hasWorkflow)
                                <form method="POST" action="{{ route($cfg['route'] . '.submit', $memo) }}"
                                    onsubmit="return confirm('Submit for approval? A {{ strtolower($cfg['label']) }} number will be issued.');">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg">Submit
                                        for Approval</button>
                                </form>
                            @else
                                <button type="button" disabled title="An approval workflow must be configured first"
                                    class="rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 opacity-50 cursor-not-allowed shadow-lg">Submit
                                    for Approval</button>
                            @endif
                        @endif

                        @if ($canVoid)
                            <form method="POST" action="{{ route($cfg['route'] . '.void', $memo) }}" x-data
                                @submit="const r = prompt('Reason for voiding this {{ strtolower($cfg['label']) }}? A reversing journal will be posted.'); if (!r) { $event.preventDefault(); return; } $refs.reason.value = r;">
                                @csrf
                                <input type="hidden" name="void_reason" x-ref="reason">
                                <button type="submit"
                                    class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100">Void</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="px-8 py-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4 text-xs">
                    <div>
                        <div class="text-gray-500">Customer</div>
                        <div class="font-medium text-gray-900">{{ $billTo['name'] }}</div>
                        <div class="text-gray-500 mt-1">TIN: <span class="text-gray-800">{{ $billTo['tin'] ?: '—' }}</span>
                        </div>
                        <div class="text-gray-500 mt-1">{{ $billTo['address'] ?: '' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Original Invoice</div>
                        <div class="font-medium text-gray-900">
                            @if ($inv)
                                <a href="{{ route('ar.invoices.show', $inv) }}"
                                    class="text-blue-600 hover:underline">{{ $inv->invoice_no }}</a>
                            @else
                                —
                            @endif
                        </div>
                        @if ($inv)
                            <div class="text-gray-500 mt-1">Dated {{ $inv->invoice_date?->format('M d, Y') }}</div>
                            <div class="text-gray-500">Balance now: {{ number_format((float) $inv->amount_due, 2) }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-gray-500">Reason</div>
                        <div class="font-medium text-gray-900">{{ $reasonLabel }}</div>
                        @if ($memo->remarks)
                            <div class="text-gray-500 mt-1">{{ $memo->remarks }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-gray-500">Total</div>
                        <div class="font-bold text-gray-900 text-base">₱{{ number_format((float) $memo->total_amount, 2) }}
                        </div>
                        <div class="text-gray-500 mt-1">{{ $taxModes[(string) $memo->tax_option] ?? '' }} ·
                            {{ $memo->currency->code ?? 'PHP' }}</div>
                        @if ($memo->approval_status === '2')
                            <div class="text-gray-500 mt-1">
                                {{ $isCredit ? 'Applied to invoice' : 'Added to invoice' }}:
                                {{ number_format((float) $memo->amount_applied, 2) }}
                                @if ($isCredit && (float) $memo->amount_unapplied > 0)
                                    · Unapplied credit: {{ number_format((float) $memo->amount_unapplied, 2) }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <h3 class="mt-6 mb-3 text-sm font-semibold text-gray-700">Line Items</h3>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-[900px] w-full text-xs">
                        <thead class="bg-gray-50 uppercase text-gray-500">
                            <tr>
                                <th class="px-2 py-2 text-left w-6">#</th>
                                <th class="px-2 py-2 text-left">Description</th>
                                <th class="px-2 py-2 text-left">{{ $cfg['account_label'] }}</th>
                                <th class="px-2 py-2 text-right">Qty</th>
                                <th class="px-2 py-2 text-right">Unit Price</th>
                                <th class="px-2 py-2 text-right">Discount</th>
                                <th class="px-2 py-2 text-left">VAT</th>
                                <th class="px-2 py-2 text-right">Tax</th>
                                <th class="px-2 py-2 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($memo->lines as $line)
                                <tr>
                                    <td class="px-2 py-2 text-gray-400">{{ $line->line_no }}</td>
                                    <td class="px-2 py-2">{{ $line->description }}</td>
                                    <td class="px-2 py-2">{{ $line->glAccount->getFormattedAccountCodeAttribute() ?? '' }}
                                        —
                                        {{ $line->glAccount->account_name ?? '' }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums">{{ (float) $line->quantity }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums">
                                        {{ number_format((float) $line->unit_price, 2) }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums">
                                        {{ number_format((float) $line->discount_amount, 2) }}</td>
                                    <td class="px-2 py-2">
                                        <span class="rounded bg-blue-50 px-2 py-0.5 text-[10px] text-blue-700">
                                            {{ $line->taxes->first()->tax->code ?? strtoupper(str_replace('_', '-', (string) $line->vat_class)) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-right tabular-nums">
                                        {{ number_format((float) $line->tax_amount, 2) }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums font-medium">
                                        {{ number_format((float) $line->total_amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <table class="w-full max-w-sm text-xs">
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="py-1.5 text-gray-500">VATable Sales</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $memo->vatable_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-gray-500">Zero-Rated Sales</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $memo->zero_rated_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-gray-500">VAT-Exempt / Non-VAT Sales</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $memo->vat_exempt_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-gray-500">Output VAT</td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ number_format((float) $memo->vat_amount, 2) }}</td>
                            </tr>
                            <tr class="font-bold text-gray-900">
                                <td class="py-2">Total</td>
                                <td class="py-2 text-right tabular-nums">
                                    ₱{{ number_format((float) $memo->total_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if ($memo->journal)
                    <h3 class="mt-6 mb-3 text-sm font-semibold text-gray-700">
                        Journal {{ $memo->journal->journal_no }}
                        <span class="font-normal text-gray-500">· posted
                            {{ $memo->posted_at?->format('M d, Y g:i A') }}</span>
                    </h3>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-2 py-2 text-left">Account</th>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-right">Debit</th>
                                    <th class="px-2 py-2 text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($memo->journal->lines as $jl)
                                    <tr>
                                        <td class="px-2 py-2">{{ $jl->glAccount->account_code ?? '' }} —
                                            {{ $jl->glAccount->account_name ?? '' }}</td>
                                        <td class="px-2 py-2 text-gray-600">{{ $jl->description }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ (float) $jl->debit ? number_format((float) $jl->debit, 2) : '' }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ (float) $jl->credit ? number_format((float) $jl->credit, 2) : '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($tx && $tx->histories->isNotEmpty())
                    <h3 class="mt-8 mb-3 text-sm font-semibold text-gray-700">Approval History</h3>
                    <ul class="space-y-2 text-xs">
                        @foreach ($tx->histories as $h)
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
@endsection
