@extends('dashboard')

@section('title', 'Review Collection')

@section('content')
    @php
        $canEditAmount = (bool) $step->can_edit_amount;
        $isEditable = $canEditAmount;
    @endphp

    <div class="mx-auto max-w-7xl">
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
                    <h1 class="text-2xl font-bold text-white">Review Collection</h1>
                    <p class="mt-1 text-sm text-emerald-100">
                        {{ $collection->displayNo() }}
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
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4 text-xs">
                    <div>
                        <div class="text-gray-500">Customer</div>
                        <div class="font-medium text-gray-900">{{ $collection->customer->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Collection Date</div>
                        <div class="font-medium text-gray-900">{{ $collection->collection_date->format('M d, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Payment</div>
                        <div class="font-medium text-gray-900">{{ $collection->paymentMethod->name ?? '—' }}</div>
                        <div class="text-gray-500 mt-1">Deposit: {{ $collection->bankAccount->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Amount Received</div>
                        <div class="font-bold text-gray-900 text-base">
                            ₱{{ number_format((float) $collection->amount_received, 2) }}
                        </div>
                    </div>
                </div>

                <h3 class="mt-6 mb-3 text-sm font-semibold text-gray-700">Invoice Applications</h3>
                <form method="POST" action="{{ route('ar.collections.approve', $transaction) }}">
                    @csrf
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-[800px] w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-2 py-2 text-left">Invoice</th>
                                    <th class="px-2 py-2 text-left">Invoice Date</th>
                                    <th class="px-2 py-2 text-right w-32">Invoice Balance</th>
                                    <th class="px-2 py-2 text-right w-40">Amount Applied</th>
                                    <th class="px-2 py-2 text-right w-32">CWT Applied</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($collection->applications as $app)
                                    <tr>
                                        <td class="px-2 py-2">
                                            {{ $app->invoice?->displayNo() ?? '—' }}
                                        </td>
                                        <td class="px-2 py-2 text-gray-600">
                                            {{ $app->invoice?->invoice_date?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-gray-700">
                                            {{ number_format((float) ($app->invoice?->amount_due ?? 0), 2) }}
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            @if ($canEditAmount)
                                                <input type="number" step="0.01" min="0"
                                                    name="applications[{{ $app->id }}][amount_applied]"
                                                    value="{{ (float) $app->amount_applied }}"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                {{ number_format((float) $app->amount_applied, 2) }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            @if ($canEditAmount)
                                                <input type="number" step="0.01" min="0"
                                                    name="applications[{{ $app->id }}][cwt_applied]"
                                                    value="{{ (float) $app->cwt_applied }}"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                {{ number_format((float) $app->cwt_applied, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-gray-200 bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-2 py-2 text-right text-gray-500">Totals</td>
                                    <td class="px-2 py-2 text-right tabular-nums font-medium">
                                        {{ number_format((float) $collection->amount_applied, 2) }}
                                    </td>
                                    <td class="px-2 py-2 text-right tabular-nums font-medium">
                                        {{ number_format((float) $collection->cwt_amount, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                        <strong>Journal preview (on final approval):</strong>
                        Dr Bank {{ number_format((float) $collection->amount_received, 2) }}
                        @if ((float) $collection->cwt_amount > 0)
                            &nbsp;·&nbsp; Dr CWT Receivable {{ number_format((float) $collection->cwt_amount, 2) }}
                        @endif
                        &nbsp;·&nbsp; Cr AR
                        {{ number_format((float) $collection->amount_applied + (float) $collection->cwt_amount, 2) }}
                        @if ((float) $collection->amount_unapplied > 0)
                            &nbsp;·&nbsp; Cr Advance/Deposit {{ number_format((float) $collection->amount_unapplied, 2) }}
                        @endif
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-900">Approval Remarks</label>
                        <textarea name="remarks" rows="2" maxlength="1000"
                            class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs"></textarea>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                        <button type="submit" formaction="{{ route('ar.collections.return', $transaction) }}"
                            onclick="return confirm('Return this collection to the requester?');"
                            class="rounded-lg border border-orange-300 bg-orange-50 px-5 py-2.5 text-sm font-medium text-orange-800 hover:bg-orange-100">
                            Return
                        </button>
                        <button type="submit" formaction="{{ route('ar.collections.reject', $transaction) }}"
                            onclick="return confirm('Reject this collection?');"
                            class="rounded-lg border border-red-300 bg-red-50 px-5 py-2.5 text-sm font-medium text-red-800 hover:bg-red-100">
                            Reject
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 shadow">
                            {{ $step->is_final_approval ? 'Approve & Post' : 'Approve & Forward' }}
                        </button>
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
@endsection
