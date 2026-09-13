@extends('dashboard')

@section('title', 'Approval Dashboard')

@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div id="success-alert"
                class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800 shadow-sm">
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
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
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

        <div class="mt-5 mb-5">
            <h1 class="text-2xl font-bold text-gray-800">Approval Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Review and process requests pending your approval</p>
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
                            @php
                                $approvable = $tx->approvable;
                                $reference = $tx->getReferenceNumber();
                                $moduleLabel = $tx->getModuleLabel();
                                $moduleBadge = $tx->getModuleBadgeClass();
                                $requestorName = $tx->getRequestorName();
                                $requestorEmail = $tx->getRequestorEmail();
                                $amount = $tx->getTotalAmount();
                                $currency = $tx->getCurrencySymbol();
                                $purpose = $tx->getPurpose();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-1.5 font-medium text-blue-600">
                                    <button type="button" data-drawer-target="drawer-tx-{{ $tx->id }}"
                                        data-drawer-show="drawer-tx-{{ $tx->id }}" data-drawer-placement="right"
                                        class="text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                                        {{ $reference }}
                                    </button>
                                </td>
                                <td class="px-3 py-1.5">
                                    <span class="rounded-full {{ $moduleBadge }} px-2.5 py-1 text-xs font-medium">
                                        {{ $moduleLabel }}
                                    </span>
                                </td>
                                <td class="px-3 py-1.5">
                                    <div class="font-medium text-gray-900">{{ $requestorName }}</div>
                                    <div class="text-gray-400 text-[10px]">{{ $requestorEmail }}</div>
                                </td>
                                <td class="px-3 py-1.5 text-gray-700">
                                    {{ $purpose }}
                                </td>
                                <td class="px-3 py-1.5 text-right tabular-nums font-medium">
                                    {{ $currency }}{{ number_format($amount, 2) }}
                                </td>
                                <td class="px-3 py-1.5 text-center">
                                    <span
                                        class="inline-flex rounded-full bg-yellow-50 px-2.5 py-1 text-[10px] font-medium text-yellow-700">
                                        Pending
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <a href="{{ $tx->reviewUrl() }}"
                                        class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                        Review
                                    </a>
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

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                    <div>
                        Showing {{ $transactions->firstItem() ?? 0 }}-{{ $transactions->lastItem() ?? 0 }}
                        of {{ $transactions->total() }}
                    </div>
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
