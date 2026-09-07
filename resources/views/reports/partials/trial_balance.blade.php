@if (isset($isBalanced))
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Trial Balance</h2>
                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($from)->format('M d, Y') }} -
                    {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</p>
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

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Account Code</th>
                        <th class="px-4 py-2 text-left">Account Name</th>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-right">Debit</th>
                        <th class="px-4 py-2 text-right">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-700">{{ $row->account_code }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $row->account_name }}</td>
                            <td class="px-4 py-2">
                                <span class="rounded-full bg-gray-50 px-2.5 py-1 text-gray-500">
                                    {{ ucfirst(strtolower($row->account_type_code)) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums">
                                {{ $row->debit_balance > 0 ? number_format($row->debit_balance, 2) : '—' }}
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums">
                                {{ $row->credit_balance > 0 ? number_format($row->credit_balance, 2) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No posted GL activity for
                                this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot class="border-t-2 border-gray-300 bg-gray-50 font-semibold text-gray-900">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right">Total</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ number_format($totalDebit, 2) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ number_format($totalCredit, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endif
