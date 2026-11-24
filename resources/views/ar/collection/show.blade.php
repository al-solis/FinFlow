@extends('dashboard')

@section('title', 'Collection — ' . $collection->displayNo())

@section('content')
    @php
        $isDraft = $collection->status === 'draft';
        $isPosted = $collection->status === 'posted';
        $isVoided = $collection->status === 'voided' || $collection->voided_at;
    @endphp

    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div id="success-alert"
                class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800 shadow-sm transition-all duration-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-4 w-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ session('success') }}
                    </div>
                    <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                        class="text-green-600 hover:text-green-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div id="error-alert"
                class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-4 w-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.334-.213 2.98-1.742 2.98H3.48c-1.53 0-2.492-1.646-1.743-2.98l6.518-11.59zM10 13a1 1 0 100-2 1 1 0 000 2zm-1-8a1 1 0 011 1v3a1 1 0 11-2 0V6a1 1 0 011-1z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ session('error') }}
                    </div>
                    <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                        class="text-red-600 hover:text-red-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if ($returnRemark)
            <div class="mt-3 mb-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800">
                <strong>Returned by approver:</strong> {{ $returnRemark->remarks }}
                <span class="text-xs text-orange-600">— {{ $returnRemark->acted_at?->format('M d, Y g:i A') }}</span>
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold text-white">{{ $collection->displayNo() }}</h1>
                            {{-- <span
                                class="inline-flex rounded-full {{ $collection->statusBadgeClass() }} px-3 py-1 text-xs font-medium">
                                {{ ucfirst($collection->status) }}
                            </span> --}}
                            <span
                                class="inline-flex rounded-full {{ $collection->approvalStatusBadgeClass() }} px-3 py-1 text-xs font-medium">
                                {{ $collection->approvalStatusLabel() }}
                            </span>
                            @if ($isVoided)
                                <span
                                    class="inline-flex rounded-full bg-red-100 text-red-700 px-3 py-1 text-xs font-medium">
                                    Voided {{ $collection->voided_at?->format('M d, Y') }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-blue-100">
                            {{ $collection->collection_date->format('M d, Y') }}
                            @if ($collection->customer)
                                · {{ $collection->customer->name }}
                            @endif
                            @if ($collection->reference_number)
                                · Ref {{ $collection->reference_number }}
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('ar.col') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                            Back
                        </a>

                        @if ($canEdit)
                            <a href="{{ route('ar.collections.edit', $collection) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 hover:bg-white">
                                Edit
                            </a>
                        @endif

                        @if ($collection->approval_status === '0' && !$collection->submitted_at)
                            <form method="POST" action="{{ route('ar.collections.destroy', $collection) }}"
                                onsubmit="return confirm('Delete draft collection {{ $collection->displayNo() }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-500/90 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-600">
                                    Delete
                                </button>
                            </form>
                        @endif

                        @if ($canSubmit)
                            @if ($hasWorkflow)
                                <form method="POST" action="{{ route('ar.collections.submit', $collection) }}">
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

                        @if ($canVoid)
                            <form method="POST" action="{{ route('ar.collections.void', $collection) }}"
                                onsubmit="return confirm('Void this collection? Invoice balances will be restored.');">
                                @csrf
                                <input type="hidden" name="void_reason" value="Voided from collection page">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-500/90 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-600">
                                    Void
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="px-8 py-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Customer</div>
                        <div class="mt-2 text-sm font-medium text-gray-900">{{ $collection->customer->name ?? '—' }}</div>
                        @if ($collection->customer?->tax_id)
                            <div class="mt-1 text-xs text-gray-600">TIN: {{ $collection->customer->tax_id }}</div>
                        @endif
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Payment</div>
                        <div class="mt-2 text-sm text-gray-900">
                            {{ $collection->paymentMethod->name ?? '—' }}
                        </div>
                        <div class="mt-1 text-xs text-gray-600">
                            Deposit to: {{ $collection->bankAccount->name ?? '—' }}
                        </div>
                        @if ($collection->check_number)
                            <div class="mt-1 text-xs text-gray-600">
                                Check {{ $collection->check_number }}
                                @if ($collection->check_bank)
                                    — {{ $collection->check_bank }}
                                @endif
                                @if ($collection->check_date)
                                    — {{ $collection->check_date->format('M d, Y') }}
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Amounts</div>
                        <div class="mt-2 flex justify-between text-xs text-gray-700">
                            <span>Received</span>
                            <span
                                class="tabular-nums font-medium">₱{{ number_format((float) $collection->amount_received, 2) }}</span>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-700">
                            <span>Applied</span>
                            <span class="tabular-nums">{{ number_format((float) $collection->amount_applied, 2) }}</span>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-700">
                            <span>CWT</span>
                            <span class="tabular-nums">{{ number_format((float) $collection->cwt_amount, 2) }}</span>
                        </div>
                        <div
                            class="mt-2 flex justify-between border-t border-gray-200 pt-2 text-sm font-bold text-gray-900">
                            <span>Unapplied</span>
                            <span class="tabular-nums">{{ number_format((float) $collection->amount_unapplied, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Applications --}}
                <h3 class="mt-6 mb-3 text-sm font-semibold text-gray-700">Applied Invoices</h3>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 uppercase text-gray-500">
                            <tr>
                                <th class="px-3 py-2 text-left">Invoice</th>
                                <th class="px-3 py-2 text-left">Invoice Date</th>
                                <th class="px-3 py-2 text-right">Amount Applied</th>
                                <th class="px-3 py-2 text-right">CWT Applied</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($collection->applications as $app)
                                <tr>
                                    <td class="px-3 py-2">
                                        @if ($app->invoice)
                                            <a href="{{ route('ar.invoices.show', $app->invoice) }}"
                                                class="text-blue-600 hover:underline">{{ $app->invoice->displayNo() }}</a>
                                        @else
                                            <span class="text-gray-400">#{{ $app->sales_invoice_id }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">
                                        {{ $app->invoice?->invoice_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        {{ number_format((float) $app->amount_applied, 2) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        {{ number_format((float) $app->cwt_applied, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">No applications.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Journal --}}
                @if ($collection->journal)
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
                                @foreach ($collection->journal->lines as $jl)
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

                @if ($tx)
                    <h3 class="mt-8 mb-3 text-sm font-semibold text-gray-700">Approval History</h3>
                    <ul class="space-y-2 text-xs">
                        @forelse ($tx->histories as $h)
                            <li class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <span class="font-medium text-gray-800">{{ ucfirst($h->action) }}</span>
                                by {{ $h->actor->name ?? 'System' }}
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
