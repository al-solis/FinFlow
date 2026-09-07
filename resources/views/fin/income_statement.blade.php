@extends('dashboard')

@section('title', 'Income Statement')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mt-5 mb-5 flex flex-wrap items-center justify-between gap-4 print:mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 print:text-xl">Income Statement</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Profit and loss statement for period {{ $from }} to {{ $to }}
                </p>
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
                <a href="{{ route('reports.export', ['type' => 'income_statement', 'from' => $from, 'to' => $to]) }}"
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
            <form id="incomeStatementForm" method="GET" action="{{ route('fin.is') }}"
                class="flex flex-wrap items-end gap-4">
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
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">View</label>
                    <select name="view"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs focus:ring-2 focus:ring-blue-500">
                        <option value="summary" @selected(($view ?? 'summary') == 'summary')>Summary</option>
                        <option value="detailed" @selected(($view ?? 'summary') == 'detailed')>Detailed</option>
                        <option value="comparative" @selected(($view ?? 'summary') == 'comparative')>Comparative</option>
                    </select>
                </div>
                <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-medium text-white hover:bg-blue-700 transition-colors">
                    Generate
                </button>
            </form>
        </div>

        <!-- Metric KPI Cards Summary (Hidden on Print) -->
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3 print:hidden">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Total Revenue</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">
                    {{ $currencySymbol }}{{ number_format($totalRevenue ?? 0, 2) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Gross Profit</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">
                    {{ $currencySymbol }}{{ number_format($grossProfit ?? 0, 2) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">Net Income</p>
                <p class="mt-1 text-xl font-semibold {{ ($netIncome ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $currencySymbol }}{{ number_format($netIncome ?? 0, 2) }}
                </p>
            </div>
        </div>

        <!-- Income Statement Content -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm print:border-none print:shadow-none">
            @include('reports.partials.income_statement', [
                'revenue' => $revenue ?? [],
                'expenses' => $expenses ?? [],
                'cogs' => $cogs ?? [],
                'otherIncome' => $otherIncome ?? [],
                'otherExpenses' => $otherExpenses ?? [],
                'totalRevenue' => $totalRevenue ?? 0,
                'totalExpenses' => $totalExpenses ?? 0,
                'totalCogs' => $totalCogs ?? 0,
                'totalOtherIncome' => $totalOtherIncome ?? 0,
                'totalOtherExpenses' => $totalOtherExpenses ?? 0,
                'grossProfit' => $grossProfit ?? 0,
                'operatingIncome' => $operatingIncome ?? 0,
                'netIncome' => $netIncome ?? 0,
                'from' => $from,
                'to' => $to,
            ])
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = id = document.getElementById('incomeStatementForm');
                if (form) {
                    form.querySelectorAll('input[type="date"], select').forEach(element => {
                        element.addEventListener('change', function() {
                            form.submit();
                        });
                    });
                }
            });
        </script>
    @endpush
@endsection
