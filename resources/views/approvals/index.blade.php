@extends('dashboard')

@section('title', 'Approval Dashboard')

@section('content')
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
                        class="text-green-600 hover:text-green-800 transition-colors duration-200">
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
                class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 shadow-sm transition-all duration-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-4 w-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ session('error') }}
                    </div>
                    <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                        class="text-red-600 hover:text-red-800 transition-colors duration-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif


        <div class="mt-5 mb-5">
            <h1 class="text-2xl font-bold text-gray-800">Approval Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Review and process disbursement requests</p>
        </div>

        <!-- Stat cards -->
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                <div class="text-sm font-medium text-blue-700">Pending Approval</div>
                <div class="mt-1 text-2xl font-bold text-blue-900">{{ $pendingApproval }}</div>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                <div class="text-sm font-medium text-green-700">Awaiting Disbursement</div>
                <div class="mt-1 text-2xl font-bold text-green-900">{{ $awaitingDisbursement }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="text-sm font-medium text-gray-500">Total Requests</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $totalRequests }}</div>
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Reference</th>
                            <th class="px-3 py-3 text-left">Type</th>
                            <th class="px-3 py-3 text-left">Requestor</th>
                            <th class="px-3 py-3 text-left">Purpose</th>
                            <th class="px-3 py-3 text-right">Amount</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $tx)
                            @php $rfd = $tx->approvable; @endphp
                            <tr class="hover:bg-gray-50">
                                <td
                                    class="px-6 py-3 font-medium text-blue-600 hover:text-blue-800 hover:underline cursor-pointer transition-colors duration-200">
                                    <button type="button" data-drawer-target="drawer-tx-{{ $tx->id }}"
                                        data-drawer-show="drawer-tx-{{ $tx->id }}" data-drawer-placement="right"
                                        data-drawer-backdrop="true"
                                        class="text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                                        RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}
                                    </button>
                                </td>

                                </td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">RFD</span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-gray-900">
                                        {{ trim(($rfd->creator->last_name ?? '') . ', ' . ($rfd->creator->first_name ?? '')) ?: $rfd->creator->name ?? 'Unknown' }}
                                    </div>
                                    <div class="text-gray-400">{{ $rfd->creator->email ?? '' }}</div>
                                </td>
                                <td class="px-3 py-3 text-gray-700">
                                    {{ $rfd->remarks ?: '—' }}
                                    <div class="text-gray-400">{{ $rfd->details->count() }} line
                                        item{{ $rfd->details->count() === 1 ? '' : 's' }}</div>
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">
                                    {{ $rfd->currency->symbol ?? '' }}{{ number_format($rfd->total_due, 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full bg-yellow-50 px-2.5 py-1 text-yellow-700">Pending</span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <button type="button" data-drawer-target="drawer-tx-{{ $tx->id }}"
                                        data-drawer-show="drawer-tx-{{ $tx->id }}" data-drawer-placement="right"
                                        data-drawer-backdrop="true"
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    Nothing waiting on your approval right now.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Drawers --}}
                @foreach ($transactions as $tx)
                    @php $rfd = $tx->approvable; @endphp

                    <div id="drawer-tx-{{ $tx->id }}"
                        class="fixed top-0 right-0 z-50 h-screen w-[520px] max-w-full translate-x-full overflow-y-auto bg-white shadow-xl transition-transform duration-300"
                        tabindex="-1" aria-labelledby="drawer-label-{{ $tx->id }}" role="dialog">

                        {{-- Drawer Header --}}
                        <div class="sticky top-0 z-10 border-b border-gray-200 bg-white px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="mb-2 flex flex-wrap gap-1">
                                        <span
                                            class="inline-flex rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-medium text-yellow-700">
                                            Pending
                                        </span>
                                        <span
                                            class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                            Request for Disbursement
                                        </span>
                                    </div>
                                    <h5 id="drawer-label-{{ $tx->id }}"
                                        class="text-lg font-semibold text-gray-900">
                                        {{ $rfd->remarks ?: 'Disbursement Request' }}
                                    </h5>
                                    <p class="text-xs text-gray-500">
                                        RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-start gap-3">
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500">
                                            Total Amount
                                        </div>
                                        <div class="text-lg font-bold text-gray-900">
                                            {{ $rfd->currency->symbol ?? '' }}{{ number_format($rfd->total_due, 2) }}
                                        </div>
                                    </div>

                                    <button type="button" data-drawer-hide="drawer-tx-{{ $tx->id }}"
                                        aria-controls="drawer-tx-{{ $tx->id }}"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Drawer Body --}}
                        <div class="px-5 py-5">
                            {{-- Request Information --}}
                            <div class="mb-5 rounded-lg bg-gray-50 p-4 text-xs">
                                <div class="mb-3 flex justify-between gap-4">
                                    <span class="shrink-0 text-gray-500">
                                        Requested By
                                    </span>
                                    <span class="text-right font-medium text-gray-800">
                                        {{ $rfd->creator->name ?? trim(($rfd->creator->first_name ?? '') . ' ' . ($rfd->creator->last_name ?? '')) }}
                                        · {{ $rfd->creator->email ?? '' }}
                                    </span>
                                </div>
                                <div class="mb-3 flex justify-between gap-4">
                                    <span class="shrink-0 text-gray-500">
                                        Date Submitted
                                    </span>
                                    <span class="text-right font-medium text-gray-800">
                                        {{ $rfd->submitted_at?->format('n/j/Y, g:i A') }}
                                    </span>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <span class="shrink-0 text-gray-500">
                                        Purpose
                                    </span>
                                    <span class="text-right font-medium text-gray-800">
                                        {{ $rfd->remarks ?: '—' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Line Items --}}
                            <div class="mb-5">
                                <div class="mb-2 flex items-center justify-between">
                                    <h6 class="text-xs font-semibold uppercase text-gray-500">
                                        Line Items ({{ $rfd->details->count() }})
                                    </h6>
                                </div>

                                <div class="overflow-hidden rounded-lg border border-gray-200">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-gray-50 text-gray-500">
                                                <tr>
                                                    <th class="w-8 px-3 py-2 text-left font-medium">
                                                        #
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-medium">
                                                        Description
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-medium">
                                                        Account
                                                    </th>
                                                    <th class="px-3 py-2 text-right font-medium">
                                                        Amount
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-gray-100">

                                                @foreach ($rfd->details as $detail)
                                                    <tr>
                                                        <td class="px-3 py-2 align-top text-gray-400">
                                                            {{ $detail->line_no }}
                                                        </td>
                                                        <td class="px-3 py-2 align-top text-gray-800">
                                                            {{ $detail->description }}
                                                        </td>
                                                        <td class="px-3 py-2 align-top">
                                                            <div class="font-medium text-blue-700">
                                                                {{ $detail->glAccount?->getFormattedAccountCodeAttribute() ?? '—' }}
                                                            </div>
                                                            <div class="text-[10px] leading-tight text-gray-400">
                                                                {{ $detail->glAccount->account_name ?? '' }}
                                                            </div>
                                                        </td>
                                                        <td
                                                            class="px-3 py-2 text-right align-top font-medium tabular-nums">
                                                            {{ number_format($detail->total_amount, 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach

                                            </tbody>

                                            <tfoot class="border-t border-gray-200 bg-gray-50">
                                                <tr>
                                                    <td colspan="3" class="px-3 py-2 text-right font-medium">
                                                        Total
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-bold text-gray-900">
                                                        {{ number_format($rfd->total_due, 2) }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Drawer Footer --}}
                        <div class="sticky bottom-0 border-t border-gray-200 bg-white px-5 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <button type="button" data-drawer-hide="drawer-tx-{{ $tx->id }}"
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100">
                                    Close
                                </button>

                                <a href="{{ $tx->reviewUrl() }}"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                                    Review
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $transactions->firstItem() ?? 0 }}-{{ $transactions->lastItem() ?? 0 }} of
                    {{ $transactions->total() }}</div>
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
@endsection
