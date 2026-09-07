@if (isset($netIncome))
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Income Statement</h2>
                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($from)->format('M d, Y') }} -
                    {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</p>
            </div>
            <div>
                <span
                    class="inline-flex rounded-full bg-{{ $netIncome >= 0 ? 'green' : 'red' }}-100 px-3 py-1 text-xs font-medium text-{{ $netIncome >= 0 ? 'green' : 'red' }}-700">
                    {{ $netIncome >= 0 ? 'Net Income' : 'Net Loss' }}: {{ number_format($netIncome, 2) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Revenue -->
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-green-50 px-4 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-green-700 text-sm">Revenue</h3>
                </div>
                <table class="min-w-full text-xs">
                    <tbody>
                        @forelse ($revenue as $rev)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-700">{{ $rev->account_code }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $rev->account_name }}</td>
                                <td class="px-4 py-2 text-right tabular-nums font-medium text-green-600">
                                    {{ number_format($rev->balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500">No revenue accounts found
                                </td>
                            </tr>
                        @endforelse
                        <tr class="bg-green-50 font-semibold">
                            <td colspan="2" class="px-4 py-2 text-right">Total Revenue</td>
                            <td class="px-4 py-2 text-right tabular-nums text-green-700">
                                {{ number_format($totalRevenue, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Expenses -->
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-red-50 px-4 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-red-700 text-sm">Expenses</h3>
                </div>
                <table class="min-w-full text-xs">
                    <tbody>
                        @forelse ($expenses as $exp)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-700">{{ $exp->account_code }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $exp->account_name }}</td>
                                <td class="px-4 py-2 text-right tabular-nums font-medium text-red-600">
                                    {{ number_format($exp->balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500">No expense accounts found
                                </td>
                            </tr>
                        @endforelse
                        <tr class="bg-red-50 font-semibold">
                            <td colspan="2" class="px-4 py-2 text-right">Total Expenses</td>
                            <td class="px-4 py-2 text-right tabular-nums text-red-700">
                                {{ number_format($totalExpenses, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="mt-6 border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700 text-sm">Summary</h3>
            </div>
            <div class="px-4 py-3 text-xs">
                <div class="flex justify-between py-1">
                    <span>Total Revenue</span>
                    <span class="font-bold text-green-600">{{ number_format($totalRevenue, 2) }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span>Less: Cost of Goods Sold</span>
                    <span class="font-bold text-red-600">{{ number_format($totalCogs, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 border-t border-gray-200 font-semibold">
                    <span>Gross Profit</span>
                    <span class="{{ $grossProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($grossProfit, 2) }}
                    </span>
                </div>
                <div class="flex justify-between py-1">
                    <span>Less: Operating Expenses</span>
                    <span class="font-bold text-red-600">{{ number_format($totalExpenses - $totalCogs, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 border-t border-gray-200 font-semibold">
                    <span>Operating Income</span>
                    <span class="{{ $operatingIncome >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($operatingIncome, 2) }}
                    </span>
                </div>
                <div class="flex justify-between py-1">
                    <span>Other Income</span>
                    <span class="font-bold text-green-600">{{ number_format($totalOtherIncome, 2) }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span>Other Expenses</span>
                    <span class="font-bold text-red-600">{{ number_format($totalOtherExpenses, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-t-2 border-gray-300 font-bold text-base">
                    <span>Net {{ $netIncome >= 0 ? 'Income' : 'Loss' }}</span>
                    <span class="{{ $netIncome >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ number_format($netIncome, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endif
