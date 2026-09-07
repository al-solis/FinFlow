<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Tax Summary Report</h2>
            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} -
                {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reports.tax.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                class="rounded-lg bg-green-600 px-4 py-2.5 text-xs font-medium text-white hover:bg-green-700">
                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Check if there's data -->
    @if ($summary->isEmpty() && $taxTransactions->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p>No tax data found for the selected period.</p>
            <p class="text-xs text-gray-400 mt-1">Try adjusting your date range or check if there are approved RFDs with
                taxes.</p>
        </div>
    @else
        <!-- Summary Table -->
        <div class="mb-6 border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-blue-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-blue-700 text-sm">Tax Code Summary</h3>
                <p class="text-xs text-blue-600 mt-0.5">{{ $totals['total_transactions'] ?? 0 }} transaction(s) found
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left">Tax Code</th>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-right">Rate (%)</th>
                            <th class="px-4 py-2 text-left">GL Account</th>
                            <th class="px-4 py-2 text-right">Taxable Amount</th>
                            <th class="px-4 py-2 text-right">Tax Amount</th>
                            <th class="px-4 py-2 text-right">GL Balance</th>
                            <th class="px-4 py-2 text-center"># Trans</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($summary as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-medium text-gray-800">
                                    {{ $row['code'] ?? ($row->code ?? '—') }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $row['name'] ?? ($row->name ?? '—') }}</td>
                                <td class="px-4 py-2">
                                    <span class="rounded-full bg-gray-50 px-2.5 py-1 text-gray-500">
                                        {{ $row['type'] ?? ($row->type ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums">
                                    {{ number_format($row['rate'] ?? ($row->rate ?? 0), 2) }}%</td>
                                <td class="px-4 py-2 text-gray-600">
                                    {{ $row['gl_account'] ?? ($row->gl_account ?? 'Not Assigned') }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-blue-600">
                                    {{ number_format($row['taxable_amount'] ?? ($row->taxable_amount ?? 0), 2) }}
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums text-purple-600">
                                    {{ number_format($row['tax_amount'] ?? ($row->tax_amount ?? 0), 2) }}
                                </td>
                                <td
                                    class="px-4 py-2 text-right tabular-nums {{ ($row['gl_balance'] ?? ($row->gl_balance ?? 0)) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($row['gl_balance'] ?? ($row->gl_balance ?? 0), 2) }}
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span
                                        class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                        {{ $row['transaction_count'] ?? ($row->transaction_count ?? 0) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    No tax data found for the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (!$summary->isEmpty())
                        <tfoot class="border-t-2 border-gray-300 bg-gray-50 font-semibold text-gray-900">
                            <tr>
                                <td colspan="5" class="px-4 py-2 text-right">Totals</td>
                                <td class="px-4 py-2 text-right tabular-nums text-blue-700">
                                    {{ number_format($totals['total_taxable'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums text-purple-700">
                                    {{ number_format($totals['total_tax_amount'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums text-green-700">
                                    {{ number_format($totals['total_gl_balance'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2 text-center text-gray-700">
                                    {{ $totals['total_transactions'] ?? 0 }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Detailed Tax Transactions -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-yellow-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-yellow-700 text-sm">Detailed Tax Transactions</h3>
                <p class="text-xs text-yellow-600 mt-0.5">From RFD Tax Details</p>
            </div>
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left">RFD #</th>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Vendor</th>
                            <th class="px-4 py-2 text-left">Tax Code</th>
                            <th class="px-4 py-2 text-left">Tax Name</th>
                            <th class="px-4 py-2 text-right">Taxable Amount</th>
                            <th class="px-4 py-2 text-right">Tax Amount</th>
                            <th class="px-4 py-2 text-left">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($taxTransactions ?? [] as $txn)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-medium text-blue-600">{{ $txn['rfd_reference'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-2 text-gray-600">
                                    {{ isset($txn['date']) ? \Carbon\Carbon::parse($txn['date'])->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-4 py-2 text-gray-700">{{ $txn['vendor_name'] ?? 'N/A' }}</td>
                                <td class="px-4 py-2">
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                        {{ $txn['tax_code'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-600">{{ $txn['tax_name'] ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-blue-600">
                                    {{ number_format($txn['taxable_amount'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums text-purple-600">
                                    {{ number_format($txn['tax_amount'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2 text-gray-500 max-w-xs truncate">
                                    {{ $txn['description'] ?? '' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    No tax transactions found for the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
