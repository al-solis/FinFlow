@if (isset($isBalanced))
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Balance Sheet</h2>
                <p class="text-xs text-gray-500">As of {{ \Carbon\Carbon::parse($asOf ?? now())->format('F d, Y') }}</p>
            </div>
            <div>
                @if ($isBalanced)
                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                        ✓ Balanced
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                        ⚠ Out of Balance
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Assets -->
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-blue-50 px-4 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-blue-700 text-sm">Assets</h3>
                </div>
                <table class="min-w-full text-xs">
                    <tbody>
                        @php $runningTotal = 0; @endphp
                        @forelse ($assets as $asset)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-700">{{ $asset->account_code }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $asset->account_name }}</td>
                                <td class="px-4 py-2 text-right tabular-nums font-medium">
                                    {{ number_format($asset->balance, 2) }}
                                </td>
                            </tr>
                            @php $runningTotal += $asset->balance; @endphp
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500">No asset accounts found
                                </td>
                            </tr>
                        @endforelse
                        <tr class="bg-blue-50 font-semibold">
                            <td colspan="2" class="px-4 py-2 text-right">Total Assets</td>
                            <td class="px-4 py-2 text-right tabular-nums text-blue-700">
                                {{ number_format($totalAssets, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Liabilities & Equity -->
            <div class="space-y-4">
                <!-- Liabilities -->
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-yellow-50 px-4 py-3 border-b border-gray-200">
                        <h3 class="font-semibold text-yellow-700 text-sm">Liabilities</h3>
                    </div>
                    <table class="min-w-full text-xs">
                        <tbody>
                            @php $runningTotal = 0; @endphp
                            @forelse ($liabilities as $liability)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="px-4 py-2 text-gray-700">{{ $liability->account_code }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $liability->account_name }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums font-medium">
                                        {{ number_format($liability->balance, 2) }}
                                    </td>
                                </tr>
                                @php $runningTotal += $liability->balance; @endphp
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-center text-gray-500">No liability accounts
                                        found</td>
                                </tr>
                            @endforelse
                            <tr class="bg-yellow-50 font-semibold">
                                <td colspan="2" class="px-4 py-2 text-right">Total Liabilities</td>
                                <td class="px-4 py-2 text-right tabular-nums text-yellow-700">
                                    {{ number_format($totalLiabilities, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Equity -->
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-purple-50 px-4 py-3 border-b border-gray-200">
                        <h3 class="font-semibold text-purple-700 text-sm">Equity</h3>
                    </div>
                    <table class="min-w-full text-xs">
                        <tbody>
                            @php $runningTotal = 0; @endphp
                            @forelse ($equity as $eq)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="px-4 py-2 text-gray-700">{{ $eq->account_code }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $eq->account_name }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums font-medium">
                                        {{ number_format($eq->balance, 2) }}
                                    </td>
                                </tr>
                                @php $runningTotal += $eq->balance; @endphp
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-center text-gray-500">No equity accounts
                                        found</td>
                                </tr>
                            @endforelse
                            <tr class="bg-purple-50 font-semibold">
                                <td colspan="2" class="px-4 py-2 text-right">Total Equity</td>
                                <td class="px-4 py-2 text-right tabular-nums text-purple-700">
                                    {{ number_format($totalEquity, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="border border-green-200 rounded-lg overflow-hidden">
                    <div class="bg-green-50 px-4 py-3 border-b border-green-200">
                        <h3 class="font-semibold text-green-700 text-sm">Summary</h3>
                    </div>
                    <div class="px-4 py-3 text-xs">
                        <div class="flex justify-between py-1">
                            <span>Total Assets</span>
                            <span class="font-bold">{{ number_format($totalAssets, 2) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-200">
                            <span>Total Liabilities + Equity</span>
                            <span class="font-bold">{{ number_format($totalLiabilities + $totalEquity, 2) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-t border-green-300 font-semibold">
                            <span>Difference</span>
                            <span class="{{ $isBalanced ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($totalAssets - ($totalLiabilities + $totalEquity), 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
