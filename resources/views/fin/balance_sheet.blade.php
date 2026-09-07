@extends('dashboard')

@section('title', 'Balance Sheet')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mt-5 mb-5 flex flex-wrap items-center justify-between gap-4 print:mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 print:text-xl">Balance Sheet</h1>
                <p class="mt-1 text-sm text-gray-500">Statement of financial position as of {{ $asOf }}</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('reports.index') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                    ← Back to Reports
                </a>
                <button onclick="window.print()"
                    class="rounded-lg bg-gray-600 px-4 py-2.5 text-xs font-medium text-white hover:bg-gray-700 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
                <a href="{{ route('reports.export', ['type' => 'balance_sheet', 'as_of' => $asOf]) }}"
                    class="rounded-lg bg-green-600 px-4 py-2.5 text-xs font-medium text-white hover:bg-green-700 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </a>
            </div>
        </div>

        <!-- Filters (Hidden on Print) -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm print:hidden">
            <form method="GET" action="{{ route('fin.bs') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">As of Date</label>
                    <input type="date" name="as_of" value="{{ $asOf }}"
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

        <!-- Chart & Quick Actions (Hidden on Print) -->
        <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3 print:hidden">
            <div
                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-2 min-h-[320px] flex flex-col justify-between">
                <h4 class="text-xs font-medium text-gray-600 mb-2">Liabilities vs. Equity Breakdown</h4>
                <div id="chartContainer" class="relative flex-grow h-64">
                    <canvas id="balanceSheetChart" data-assets="{{ json_encode($assets ?? []) }}"
                        data-liabilities="{{ json_encode($liabilities ?? []) }}"
                        data-equity="{{ json_encode($equity ?? []) }}">
                    </canvas>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm flex flex-col justify-between">
                <div>
                    <h4 class="text-xs font-medium text-gray-600 mb-3">Quick Actions</h4>
                    <div class="space-y-3">
                        <a href="{{ route('reports.export', ['type' => 'balance_sheet', 'as_of' => $asOf, 'format' => 'csv']) }}"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Download CSV
                        </a>
                        <a href="{{ route('gl.trial') }}"
                            class="block w-full rounded-lg border border-blue-300 bg-blue-50 px-4 py-2.5 text-center text-xs font-medium text-blue-700 hover:bg-blue-100 transition-colors">
                            View Trial Balance
                        </a>
                        <a href="{{ route('reports.export', ['type' => 'balance_sheet', 'as_of' => $asOf, 'format' => 'excel']) }}"
                            class="block w-full rounded-lg border border-green-300 bg-green-50 px-4 py-2.5 text-center text-xs font-medium text-green-700 hover:bg-green-100 transition-colors">
                            Download Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance Sheet Table Component -->
        <div
            class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden print:border-none print:shadow-none">
            @include('reports.partials.balance_sheet', [
                'assets' => $assets ?? [],
                'liabilities' => $liabilities ?? [],
                'equity' => $equity ?? [],
                'totalAssets' => $totalAssets ?? 0,
                'totalLiabilities' => $totalLiabilities ?? 0,
                'totalEquity' => $totalEquity ?? 0,
                'asOf' => $asOf,
                'isBalanced' => $isBalanced ?? true,
            ])
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const dateInput = document.querySelector('input[name="as_of"]');
                if (dateInput) {
                    dateInput.addEventListener('change', function() {
                        this.closest('form').submit();
                    });
                }

                const canvas = document.getElementById('balanceSheetChart');
                const container = document.getElementById('chartContainer');

                if (canvas && container) {
                    try {
                        const liabilities = JSON.parse(canvas.dataset.liabilities || '[]');
                        const equity = JSON.parse(canvas.dataset.equity || '[]');

                        const totalLiabilities = liabilities.reduce((sum, l) => sum + parseFloat(l.balance || 0), 0);
                        const totalEquity = equity.reduce((sum, e) => sum + parseFloat(e.balance || 0), 0);

                        if (totalLiabilities > 0 || totalEquity > 0) {
                            new Chart(canvas, {
                                type: 'doughnut',
                                data: {
                                    labels: ['Liabilities', 'Equity'],
                                    datasets: [{
                                        data: [totalLiabilities, totalEquity],
                                        backgroundColor: [
                                            'rgba(234, 179, 8, 0.8)',
                                            'rgba(168, 85, 247, 0.8)'
                                        ],
                                        borderColor: [
                                            'rgb(234, 179, 8)',
                                            'rgb(168, 85, 247)'
                                        ],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                boxWidth: 12,
                                                padding: 12,
                                                font: {
                                                    size: 11,
                                                    weight: '500'
                                                }
                                            }
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    let total = context.dataset.data.reduce((a, b) => a + b,
                                                        0);
                                                    let percentage = total > 0 ? ((context.parsed / total) *
                                                        100).toFixed(1) : 0;
                                                    let formattedVal = new Intl.NumberFormat('en-US', {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2
                                                    }).format(context.parsed);
                                                    return `${context.label}: $${formattedVal} (${percentage}%)`;
                                                }
                                            }
                                        }
                                    },
                                    cutout: '60%'
                                }
                            });
                        } else {
                            container.innerHTML = `
                                <div class="flex items-center justify-center h-full text-gray-400 text-sm">
                                    <div class="text-center">
                                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p>No financial data available for this date</p>
                                    </div>
                                </div>
                            `;
                        }
                    } catch (e) {
                        console.error('Error parsing balance sheet chart data:', e);
                    }
                }
            });
        </script>
    @endpush
@endsection
