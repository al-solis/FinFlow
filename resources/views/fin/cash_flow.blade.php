@extends('dashboard')

@section('title', 'Cash Flow Statement')

@php
    use App\Services\SystemSettings;
    $currencySymbol = $currencySymbol ?? SystemSettings::getCurrencySymbol();
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mt-5 mb-5 flex items-center justify-between print:mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 print:text-xl">Cash Flow Statement</h1>
                <p class="mt-1 text-sm text-gray-500">Statement of cash flows for period {{ $from }} to
                    {{ $to }}</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('reports.index') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    ← Back to Reports
                </a>
                <button onclick="window.print()"
                    class="inline-flex items-center rounded-lg bg-gray-600 px-4 py-2.5 text-xs font-medium text-white hover:bg-gray-700 transition-colors">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
                <a href="{{ route('reports.export', ['type' => 'cash_flow', 'from' => $from, 'to' => $to]) }}"
                    class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2.5 text-xs font-medium text-white hover:bg-green-700 transition-colors">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </a>
            </div>
        </div>

        <!-- Filters (Hidden on Print) -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm print:hidden">
            <form id="cashFlowForm" method="GET" action="{{ route('fin.cf') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">From</label>
                    <input type="date" name="from" value="{{ $from }}"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">To</label>
                    <input type="date" name="to" value="{{ $to }}"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-medium text-white hover:bg-blue-700 transition-colors">
                    Generate
                </button>
            </form>
        </div>

        @if (!isset($cashMovements) || $cashMovements->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-12 text-center shadow-sm">
                <svg class="mx-auto mb-4 h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v1m0 1v1m0 1v1m0 1v1m0 1v1m0 1v1m0 1v1" />
                </svg>
                <h3 class="mb-2 text-lg font-medium text-gray-900">No Cash Flow Data</h3>
                <p class="text-sm text-gray-500">No cash movements found for the selected period.</p>
            </div>
        @else
            <!-- Cash Flow Content -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm print:border-none print:p-0 print:shadow-none">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Cash Flow Statement</h2>
                        <p class="text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} -
                            {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <span
                            class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 border border-blue-200">
                            Opening: {{ SystemSettings::formatCurrency($openingCash) }}
                        </span>
                        <span
                            class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700 border border-green-200">
                            Closing: {{ SystemSettings::formatCurrency($closingCash) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
                    <!-- Operating Activities -->
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <div class="border-b border-gray-200 bg-blue-50 px-4 py-3">
                            <h3 class="text-sm font-semibold text-blue-700">Operating Activities</h3>
                        </div>
                        <div class="p-3 text-xs">
                            <div class="space-y-1 max-h-48 overflow-y-auto">
                                @forelse ($operatingActivities as $activity)
                                    <div class="flex justify-between border-b border-gray-100 py-1">
                                        <span
                                            class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $activity->source_module)) }}</span>
                                        <span
                                            class="font-medium {{ $activity->net_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ SystemSettings::formatCurrency($activity->net_change) }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="py-2 text-center text-gray-500">No operating activities</p>
                                @endforelse
                            </div>
                            <div class="mt-2 border-t-2 border-gray-300 pt-2 font-semibold">
                                <div class="flex justify-between">
                                    <span>Net Operating Cash Flow</span>
                                    <span class="{{ ($netOperating ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ SystemSettings::formatCurrency($netOperating ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Investing Activities -->
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <div class="border-b border-gray-200 bg-purple-50 px-4 py-3">
                            <h3 class="text-sm font-semibold text-purple-700">Investing Activities</h3>
                        </div>
                        <div class="p-3 text-xs">
                            <div class="space-y-1 max-h-48 overflow-y-auto">
                                @forelse ($investingActivities as $activity)
                                    <div class="flex justify-between border-b border-gray-100 py-1">
                                        <span
                                            class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $activity->source_module)) }}</span>
                                        <span
                                            class="font-medium {{ $activity->net_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ SystemSettings::formatCurrency($activity->net_change) }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="py-2 text-center text-gray-500">No investing activities</p>
                                @endforelse
                            </div>
                            <div class="mt-2 border-t-2 border-gray-300 pt-2 font-semibold">
                                <div class="flex justify-between">
                                    <span>Net Investing Cash Flow</span>
                                    <span class="{{ ($netInvesting ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ SystemSettings::formatCurrency($netInvesting ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financing Activities -->
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <div class="border-b border-gray-200 bg-green-50 px-4 py-3">
                            <h3 class="text-sm font-semibold text-green-700">Financing Activities</h3>
                        </div>
                        <div class="p-3 text-xs">
                            <div class="space-y-1 max-h-48 overflow-y-auto">
                                @forelse ($financingActivities as $activity)
                                    <div class="flex justify-between border-b border-gray-100 py-1">
                                        <span
                                            class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $activity->source_module)) }}</span>
                                        <span
                                            class="font-medium {{ $activity->net_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ SystemSettings::formatCurrency($activity->net_change) }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="py-2 text-center text-gray-500">No financing activities</p>
                                @endforelse
                            </div>
                            <div class="mt-2 border-t-2 border-gray-300 pt-2 font-semibold">
                                <div class="flex justify-between">
                                    <span>Net Financing Cash Flow</span>
                                    <span class="{{ ($netFinancing ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ SystemSettings::formatCurrency($netFinancing ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reconciliation Summary Bar -->
                <div class="rounded-lg bg-gray-50 p-4 border border-gray-200">
                    <div class="flex flex-wrap justify-between items-center text-sm font-semibold text-gray-800 gap-2">
                        <span>Net Change in Cash:
                            {{ SystemSettings::formatCurrency(($netOperating ?? 0) + ($netInvesting ?? 0) + ($netFinancing ?? 0)) }}</span>
                        <span>Ending Cash Balance: {{ SystemSettings::formatCurrency($closingCash) }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('cashFlowForm');
                if (form) {
                    form.querySelectorAll('input[type="date"]').forEach(element => {
                        element.addEventListener('change', function() {
                            form.submit();
                        });
                    });
                }
            });
        </script>
    @endpush
@endsection
