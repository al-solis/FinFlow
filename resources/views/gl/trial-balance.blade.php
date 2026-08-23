@extends('dashboard')

@section('title', 'Trial Balance')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Trial Balance</h1>
                    <p class="mt-1 text-sm text-gray-500">Posted GL activity for the selected period</p>
                </div>
                <a href="{{ route('gl.gr') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    General Ledger →
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('gl.trial') }}"
                class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">From</label>
                    <input type="date" name="from" value="{{ $from }}"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">To</label>
                    <input type="date" name="to" value="{{ $to }}"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                </div>
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    Filter
                </button>
            </form>

            <!-- Balance check banner -->
            @if ($isBalanced)
                <div
                    class="mb-5 flex items-center gap-2 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                    <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    Trial balance is in balance — total debits equal total credits.
                </div>
            @else
                <div
                    class="mb-5 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                    <svg class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.334-.213 2.98-1.742 2.98H3.48c-1.53 0-2.492-1.646-1.743-2.98l6.518-11.59zM10 13a1 1 0 100-2 1 1 0 000 2zm-1-8a1 1 0 011 1v3a1 1 0 11-2 0V6a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Out of balance by {{ number_format(abs($totalDebit - $totalCredit), 2) }} — check for unbalanced
                    journals.
                </div>
            @endif

            <!-- Table -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                            <tr>
                                <th class="px-6 py-3 text-left">Account Code</th>
                                <th class="px-3 py-3 text-left">Account Name</th>
                                <th class="px-3 py-3 text-left">Type</th>
                                <th class="px-3 py-3 text-right">Debit</th>
                                <th class="px-3 py-3 text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('gl.gr', ['account_id' => $row->account_id, 'from' => $from, 'to' => $to]) }}"
                                            class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                            {{ $row->account_code }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-3 text-gray-700">{{ $row->account_name }}</td>
                                    <td class="px-3 py-3">
                                        <span class="rounded-full bg-gray-50 px-2.5 py-1 text-gray-500">
                                            {{ ucfirst(strtolower($row->account_type_code)) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums">
                                        {{ $row->debit_balance > 0 ? number_format($row->debit_balance, 2) : '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums">
                                        {{ $row->credit_balance > 0 ? number_format($row->credit_balance, 2) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        No posted GL activity for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($rows->isNotEmpty())
                            <tfoot class="border-t-2 border-gray-300 bg-gray-50 font-semibold text-gray-900">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right">Total</td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ number_format($totalDebit, 2) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ number_format($totalCredit, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
