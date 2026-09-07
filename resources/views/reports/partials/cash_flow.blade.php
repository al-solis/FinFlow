<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Cash Flow Statement</h2>
            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($from)->format('M d, Y') }} -
                {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</p>
        </div>
        <div class="flex gap-2">
            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                Opening: {{ $currencySymbol ?? '₱' }}{{ number_format($openingCash, 2) }}
            </span>
            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                Closing: {{ $currencySymbol ?? '₱' }}{{ number_format($closingCash, 2) }}
            </span>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-200">
            <div class="text-xs text-gray-500">Operating</div>
            <div class="font-bold {{ $netOperating >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $currencySymbol ?? '₱' }}{{ number_format($netOperating, 2) }}
            </div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-200">
            <div class="text-xs text-gray-500">Investing</div>
            <div class="font-bold {{ $netInvesting >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $currencySymbol ?? '₱' }}{{ number_format($netInvesting, 2) }}
            </div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-200">
            <div class="text-xs text-gray-500">Financing</div>
            <div class="font-bold {{ $netFinancing >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $currencySymbol ?? '₱' }}{{ number_format($netFinancing, 2) }}
            </div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-200">
            <div class="text-xs text-gray-500">Net Change</div>
            <div class="font-bold {{ $totalNetChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $currencySymbol ?? '₱' }}{{ number_format($totalNetChange, 2) }}
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="border border-gray-200 rounded-lg p-3" style="height: 300px;">
            <h4 class="text-xs font-medium text-gray-600 mb-2">Daily Cash Flow Trend</h4>
            <canvas id="cashFlowDailyChart" data-daily='@json($dailyMovements ?? [])'
                data-currency="{{ $currencySymbol ?? '₱' }}" style="height: 240px; width: 100%;">
            </canvas>
        </div>
        <div class="border border-gray-200 rounded-lg p-3" style="height: 300px;">
            <h4 class="text-xs font-medium text-gray-600 mb-2">Cash Flow by Activity</h4>
            <canvas id="cashFlowActivityChart" data-operating="{{ $netOperating ?? 0 }}"
                data-investing="{{ $netInvesting ?? 0 }}" data-financing="{{ $netFinancing ?? 0 }}"
                data-currency="{{ $currencySymbol ?? '₱' }}" style="height: 240px; width: 100%;">
            </canvas>
        </div>
    </div>

    <!-- Activities Detail -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
        <!-- Operating Activities -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-blue-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-blue-700 text-sm">Operating Activities</h3>
            </div>
            <div class="p-3 text-xs max-h-48 overflow-y-auto">
                @forelse ($operatingActivities as $activity)
                    <div class="flex justify-between py-1 border-b border-gray-100">
                        <span
                            class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $activity->source_module)) }}</span>
                        <span class="font-medium {{ $activity->net_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $currencySymbol ?? '₱' }}{{ number_format($activity->net_change, 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 py-2 text-center">No operating activities</p>
                @endforelse
                <div class="mt-2 pt-2 border-t-2 border-gray-300 font-semibold">
                    <div class="flex justify-between">
                        <span>Net Operating Cash Flow</span>
                        <span class="{{ $netOperating >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $currencySymbol ?? '₱' }}{{ number_format($netOperating, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Investing Activities -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-purple-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-purple-700 text-sm">Investing Activities</h3>
            </div>
            <div class="p-3 text-xs max-h-48 overflow-y-auto">
                @forelse ($investingActivities as $activity)
                    <div class="flex justify-between py-1 border-b border-gray-100">
                        <span
                            class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $activity->source_module)) }}</span>
                        <span class="font-medium {{ $activity->net_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $currencySymbol ?? '₱' }}{{ number_format($activity->net_change, 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 py-2 text-center">No investing activities</p>
                @endforelse
                <div class="mt-2 pt-2 border-t-2 border-gray-300 font-semibold">
                    <div class="flex justify-between">
                        <span>Net Investing Cash Flow</span>
                        <span class="{{ $netInvesting >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $currencySymbol ?? '₱' }}{{ number_format($netInvesting, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financing Activities -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-green-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-green-700 text-sm">Financing Activities</h3>
            </div>
            <div class="p-3 text-xs max-h-48 overflow-y-auto">
                @forelse ($financingActivities as $activity)
                    <div class="flex justify-between py-1 border-b border-gray-100">
                        <span
                            class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $activity->source_module)) }}</span>
                        <span class="font-medium {{ $activity->net_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $currencySymbol ?? '₱' }}{{ number_format($activity->net_change, 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 py-2 text-center">No financing activities</p>
                @endforelse
                <div class="mt-2 pt-2 border-t-2 border-gray-300 font-semibold">
                    <div class="flex justify-between">
                        <span>Net Financing Cash Flow</span>
                        <span class="{{ $netFinancing >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $currencySymbol ?? '₱' }}{{ number_format($netFinancing, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-700 text-sm">Summary</h3>
        </div>
        <div class="px-4 py-3 text-sm">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div>
                    <div class="text-xs text-gray-500">Opening Cash</div>
                    <div class="font-bold text-gray-900">
                        {{ $currencySymbol ?? '₱' }}{{ number_format($openingCash, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Operating</div>
                    <div class="font-bold {{ $netOperating >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $currencySymbol ?? '₱' }}{{ number_format($netOperating, 2) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Investing</div>
                    <div class="font-bold {{ $netInvesting >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $currencySymbol ?? '₱' }}{{ number_format($netInvesting, 2) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Financing</div>
                    <div class="font-bold {{ $netFinancing >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $currencySymbol ?? '₱' }}{{ number_format($netFinancing, 2) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Net Change</div>
                    <div class="font-bold {{ $totalNetChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $currencySymbol ?? '₱' }}{{ number_format($totalNetChange, 2) }}
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between font-bold text-lg">
                <span>Closing Cash Balance</span>
                <span class="text-blue-700">{{ $currencySymbol ?? '₱' }}{{ number_format($closingCash, 2) }}</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Daily Cash Flow Chart
            const dailyCanvas = document.getElementById('cashFlowDailyChart');
            if (dailyCanvas) {
                const dailyData = JSON.parse(dailyCanvas.dataset.daily || '[]');
                const currency = dailyCanvas.dataset.currency || '₱';

                if (dailyData.length > 0) {
                    const labels = dailyData.map(d => new Date(d.date).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric'
                    }));
                    const values = dailyData.map(d => d.net_change);

                    // Calculate cumulative
                    let cumulative = 0;
                    const cumulativeValues = values.map(v => {
                        cumulative += v;
                        return cumulative;
                    });

                    new Chart(dailyCanvas, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Daily Cash Flow',
                                data: values,
                                backgroundColor: values.map(v => v >= 0 ? 'rgba(34, 197, 94, 0.7)' :
                                    'rgba(239, 68, 68, 0.7)'),
                                borderColor: values.map(v => v >= 0 ? 'rgb(34, 197, 94)' :
                                    'rgb(239, 68, 68)'),
                                borderWidth: 1,
                                borderRadius: 3,
                                order: 2
                            }, {
                                label: 'Cumulative Balance',
                                data: cumulativeValues,
                                type: 'line',
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                order: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        boxWidth: 12,
                                        padding: 12,
                                        font: {
                                            size: 10,
                                            weight: '600'
                                        },
                                        color: '#1f2937'
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + currency +
                                                new Intl.NumberFormat('en-US', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                }).format(context.parsed.y);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        font: {
                                            size: 9
                                        },
                                        callback: function(value) {
                                            return currency + (value >= 1000 ? (value / 1000) + 'k' :
                                                value);
                                        }
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: {
                                            size: 8
                                        },
                                        maxRotation: 45,
                                        minRotation: 0
                                    }
                                }
                            }
                        }
                    });
                } else {
                    dailyCanvas.parentElement.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-400 text-sm">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p>No daily cash flow data</p>
                    </div>
                </div>
            `;
                }
            }

            // Activity Breakdown Chart
            const activityCanvas = document.getElementById('cashFlowActivityChart');
            if (activityCanvas) {
                const operating = parseFloat(activityCanvas.dataset.operating || 0);
                const investing = parseFloat(activityCanvas.dataset.investing || 0);
                const financing = parseFloat(activityCanvas.dataset.financing || 0);
                const currency = activityCanvas.dataset.currency || '₱';

                if (operating !== 0 || investing !== 0 || financing !== 0) {
                    new Chart(activityCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: ['Operating', 'Investing', 'Financing'],
                            datasets: [{
                                data: [Math.abs(operating), Math.abs(investing), Math.abs(
                                    financing)],
                                backgroundColor: [
                                    'rgba(59, 130, 246, 0.8)',
                                    'rgba(168, 85, 247, 0.8)',
                                    'rgba(34, 197, 94, 0.8)'
                                ],
                                borderColor: [
                                    'rgb(59, 130, 246)',
                                    'rgb(168, 85, 247)',
                                    'rgb(34, 197, 94)'
                                ],
                                borderWidth: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        boxWidth: 12,
                                        padding: 12,
                                        font: {
                                            size: 10,
                                            weight: '600'
                                        },
                                        color: '#1f2937'
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b,
                                                0);
                                            const percentage = ((context.parsed / total) * 100).toFixed(
                                                1);
                                            return context.label + ': ' + currency +
                                                new Intl.NumberFormat('en-US', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                }).format(context.parsed) +
                                                ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            },
                            cutout: '55%'
                        }
                    });
                } else {
                    activityCanvas.parentElement.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-400 text-sm">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p>No activity data</p>
                    </div>
                </div>
            `;
                }
            }
        });
    </script>
@endpush
