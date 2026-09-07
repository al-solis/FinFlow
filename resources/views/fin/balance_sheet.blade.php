@extends('dashboard')

@section('title', 'Balance Sheet')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mt-5 mb-5 flex flex-wrap items-center justify-between gap-4 print:mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 print:text-xl">
                    Balance Sheet
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Statement of financial position as of {{ $asOf }}
                </p>
            </div>

            <div class="flex gap-2 print:hidden">
                <a href="{{ route('reports.index') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                    ← Back to Reports
                </a>

                <button onclick="window.print()"
                    class="inline-flex items-center rounded-lg bg-gray-600 px-4 py-2.5 text-xs font-medium text-white hover:bg-gray-700">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H9a2 2 0 00-2 2v4m8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>

                <a href="{{ route('reports.export', ['type' => 'balance_sheet', 'as_of' => $asOf]) }}"
                    class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2.5 text-xs font-medium text-white hover:bg-green-700">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707l5.414 5.414V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm print:hidden">
            <form method="GET" action="{{ route('fin.bs') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">
                        As of Date
                    </label>
                    <input type="date" name="as_of" value="{{ $asOf }}"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">
                        View
                    </label>
                    <select name="view"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs focus:ring-2 focus:ring-blue-500">
                        <option value="summary" @selected(($view ?? 'summary') == 'summary')>Summary</option>
                        <option value="detailed" @selected(($view ?? 'summary') == 'detailed')>Detailed</option>
                        <option value="comparative" @selected(($view ?? 'summary') == 'comparative')>Comparative</option>
                    </select>
                </div>

                <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-medium text-white transition-colors hover:bg-blue-700">
                    Generate
                </button>
            </form>
        </div>

        <!-- Chart & Quick Actions -->
        <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3 print:hidden">
            <!-- Chart -->
            <div class="flex min-h-[320px] flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="text-xs font-medium text-gray-600">
                        Liabilities vs. Equity Breakdown
                    </h4>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full" style="background-color:#eab308"></span>
                            <span class="text-xs text-gray-600">Liabilities</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full" style="background-color:#a855f7"></span>
                            <span class="text-xs text-gray-600">Equity</span>
                        </div>
                    </div>
                </div>

                <div id="chartContainer" class="relative flex-grow" style="min-height:260px;width:100%;">
                    <div id="balanceSheetChart" class="w-full" style="min-height:260px;"></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div>
                    <h4 class="mb-3 text-xs font-medium text-gray-600">Quick Actions</h4>
                    <div class="space-y-3">
                        <a href="{{ route('reports.export', ['type' => 'balance_sheet', 'as_of' => $asOf, 'format' => 'csv']) }}"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50">
                            Download CSV
                        </a>
                        <a href="{{ route('gl.trial') }}"
                            class="block w-full rounded-lg border border-blue-300 bg-blue-50 px-4 py-2.5 text-center text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100">
                            View Trial Balance
                        </a>
                        <a href="{{ route('reports.export', ['type' => 'balance_sheet', 'as_of' => $asOf, 'format' => 'excel']) }}"
                            class="block w-full rounded-lg border border-green-300 bg-green-50 px-4 py-2.5 text-center text-xs font-medium text-green-700 transition-colors hover:bg-green-100">
                            Download Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance Sheet Table -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm print:border-none print:shadow-none">
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
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('=== Balance Sheet Chart Initialization ===');

                const dateInput = document.querySelector('input[name="as_of"]');
                if (dateInput) {
                    dateInput.addEventListener('change', function() {
                        this.closest('form').submit();
                    });
                }

                function initChart() {
                    const chartElement = document.getElementById('balanceSheetChart');
                    const chartContainer = document.getElementById('chartContainer');

                    if (!chartElement || !chartContainer) {
                        console.error('Chart element not found');
                        return;
                    }

                    // Check if ApexCharts is loaded
                    if (typeof window.ApexCharts === 'undefined') {
                        console.error('ApexCharts is NOT loaded');
                        chartElement.innerHTML = `
                            <div class="flex h-full min-h-[260px] items-center justify-center text-sm text-red-500">
                                <div class="text-center">
                                    <p class="font-medium">ApexCharts library is not loaded</p>
                                    <p class="mt-1 text-xs">Please check your internet connection</p>
                                </div>
                            </div>
                        `;
                        return;
                    }

                    console.log('ApexCharts is loaded:', typeof window.ApexCharts);

                    // Get data from PHP
                    let liabilitiesData = @json($liabilities ?? []);
                    let equityData = @json($equity ?? []);

                    console.log('Raw liabilities data:', liabilitiesData);
                    console.log('Raw equity data:', equityData);
                    console.log('Data types:', {
                        liabilitiesType: Array.isArray(liabilitiesData) ? 'array' : typeof liabilitiesData,
                        equityType: Array.isArray(equityData) ? 'array' : typeof equityData,
                        liabilitiesLength: Array.isArray(liabilitiesData) ? liabilitiesData.length : 'N/A',
                        equityLength: Array.isArray(equityData) ? equityData.length : 'N/A'
                    });

                    // Calculate totals
                    let totalLiabilities = 0;
                    let totalEquity = 0;

                    if (Array.isArray(liabilitiesData)) {
                        totalLiabilities = liabilitiesData.reduce((sum, item) => {
                            const balance = parseFloat(item?.balance || 0);
                            console.log('Liabilities item:', item, 'Balance:', balance);
                            return sum + balance;
                        }, 0);
                    }

                    if (Array.isArray(equityData)) {
                        totalEquity = equityData.reduce((sum, item) => {
                            const balance = parseFloat(item?.balance || 0);
                            console.log('Equity item:', item, 'Balance:', balance);
                            return sum + balance;
                        }, 0);
                    }

                    // If both totals are 0, try using totalLiabilities and totalEquity from the controller
                    if (totalLiabilities === 0 && totalEquity === 0) {
                        const controllerTotalLiabilities = @json($totalLiabilities ?? 0);
                        const controllerTotalEquity = @json($totalEquity ?? 0);

                        console.log('Controller totals:', {
                            totalLiabilities: controllerTotalLiabilities,
                            totalEquity: controllerTotalEquity
                        });

                        if (controllerTotalLiabilities > 0 || controllerTotalEquity > 0) {
                            totalLiabilities = parseFloat(controllerTotalLiabilities) || 0;
                            totalEquity = parseFloat(controllerTotalEquity) || 0;
                            console.log('Using controller totals:', {
                                totalLiabilities,
                                totalEquity
                            });
                        }
                    }

                    // console.log('Final totals:', {
                    //     totalLiabilities,
                    //     totalEquity,
                    //     total: totalLiabilities + totalEquity
                    // });

                    // alert('Total Liabilities: ' + totalLiabilities + '\nTotal Equity: ' + totalEquity);

                    // // Clear previous content
                    // chartElement.innerHTML = '';

                    // // If no data, show sample data for demonstration
                    // let useRealData = (totalLiabilities > 0 || totalEquity > 0);

                    // if (!useRealData) {
                    //     console.warn('No real data found, using sample data for demonstration');
                    //     // Use sample data for testing
                    //     totalLiabilities = 150000;
                    //     totalEquity = 250000;

                    //     // Show a notice that sample data is being used
                    //     chartElement.innerHTML = `
            //         <div class="mb-2 rounded-lg bg-yellow-50 p-2 text-center text-xs text-yellow-800">
            //             ⚠️ No data available - showing sample data
            //         </div>
            //     `;
                    // }

                    // Check if there's any data to display
                    if (totalLiabilities === 0 && totalEquity === 0) {
                        chartElement.innerHTML += `
                            <div class="flex h-full min-h-[220px] items-center justify-center text-gray-400 text-sm">
                                <div class="text-center">
                                    <svg class="mx-auto mb-2 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707L19 19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p>No financial data available for this date</p>
                                    <p class="mt-1 text-xs text-gray-400">Try selecting a different date</p>
                                </div>
                            </div>
                        `;
                        return;
                    }

                    // Create chart options
                    const options = {
                        series: [totalLiabilities, totalEquity],
                        chart: {
                            type: 'donut',
                            height: 280,
                            width: '100%',
                            toolbar: {
                                show: false
                            },
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800
                            }
                        },
                        labels: ['Liabilities', 'Equity'],
                        colors: ['#eab308', '#a855f7'],
                        legend: {
                            show: false
                        },
                        stroke: {
                            width: 2
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '60%',
                                    labels: {
                                        show: true,
                                        name: {
                                            show: true,
                                            fontSize: '14px',
                                            fontWeight: 600,
                                            color: '#374151'
                                        },
                                        value: {
                                            show: true,
                                            fontSize: '16px',
                                            fontWeight: 700,
                                            color: '#111827',
                                            formatter: function(val) {
                                                return '$' + Number(val).toLocaleString('en-US', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                });
                                            }
                                        },
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            fontSize: '12px',
                                            fontWeight: 500,
                                            color: '#6B7280',
                                            formatter: function(w) {
                                                const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                                return total.toLocaleString('en-US', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                });
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return Number(val).toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        },
                        responsive: [{
                            breakpoint: 480,
                            options: {
                                chart: {
                                    height: 250
                                },
                                legend: {
                                    show: true,
                                    position: 'bottom',
                                    fontSize: '12px'
                                },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: '70%'
                                        }
                                    }
                                }
                            }
                        }]
                    };

                    try {
                        const chart = new window.ApexCharts(chartElement, options);
                        chart.render();
                        window.balanceSheetChart = chart;
                        console.log('Chart rendered successfully with data:', {
                            liabilities: totalLiabilities,
                            equity: totalEquity
                        });
                    } catch (error) {
                        console.error('Chart render error:', error);
                        chartElement.innerHTML += `
                            <div class="flex h-full min-h-[220px] items-center justify-center text-sm text-red-500">
                                <div class="text-center">
                                    <p class="font-medium">Error loading chart</p>
                                    <p class="mt-1 text-xs">${error.message}</p>
                                </div>
                            </div>
                        `;
                    }
                }

                // Initialize chart
                initChart();

                // Handle resize
                let resizeTimer;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function() {
                        if (window.balanceSheetChart) {
                            window.balanceSheetChart.updateOptions({
                                chart: {
                                    width: '100%'
                                }
                            });
                        }
                    }, 250);
                });
            });
        </script>
    @endpush
@endsection
