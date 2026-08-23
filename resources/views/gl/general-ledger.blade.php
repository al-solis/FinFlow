@extends('dashboard')

@section('title', 'General Ledger')

@section('content')
    @php
        use Carbon\Carbon;
    @endphp
    <div class="mx-auto max-w-6xl">
        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class=" mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">General Ledger</h1>
                    <p class="mt-1 text-sm text-gray-500">Posted transaction detail for a single account</p>
                </div>
                <a href="{{ route('gl.trial') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    ← Trial Balance
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('gl.gr') }}"
                class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
                <div class="min-w-[260px] flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Account</label>
                    <select name="account_id" required
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">— Select an account —</option>
                        @foreach ($accounts as $acct)
                            <option value="{{ $acct->id }}" @selected($accountId == $acct->id)>
                                {{ $acct->account_code }} &mdash; {{ $acct->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
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
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-medium text-white hover:bg-blue-700">
                    View
                </button>
            </form>

            @if (!$account)
                <div
                    class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">
                    Select an account above to view its ledger.
                </div>
            @else
                <!-- Account summary -->
                <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <div class="text-sm font-medium text-gray-500">Account</div>
                        <div class="mt-1 text-lg font-bold text-gray-900">{{ $account->account_code }}</div>
                        <div class="text-xs text-gray-500">{{ $account->account_name }}</div>
                    </div>
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                        <div class="text-sm font-medium text-blue-700">Opening Balance</div>
                        <div class="mt-1 text-lg font-bold text-blue-900">{{ number_format($openingBalance, 2) }}</div>
                        <div class="text-xs text-blue-600">as of {{ Carbon::parse($from)->format('M d, Y') }}</div>
                    </div>
                    <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                        <div class="text-sm font-medium text-green-700">Closing Balance</div>
                        <div class="mt-1 text-lg font-bold text-green-900">{{ number_format($closingBalance, 2) }}</div>
                        <div class="text-xs text-green-600">as of {{ Carbon::parse($to)->format('M d, Y') }}</div>
                    </div>
                </div>

                <!-- Ledger table -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-6 py-3 text-left">Date</th>
                                    <th class="px-3 py-3 text-left">Journal No</th>
                                    <th class="px-3 py-3 text-left">Source</th>
                                    <th class="px-3 py-3 text-left">Description</th>
                                    <th class="px-3 py-3 text-right">Debit</th>
                                    <th class="px-3 py-3 text-right">Credit</th>
                                    <th class="px-3 py-3 text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr class="bg-gray-50">
                                    <td colspan="6" class="px-6 py-2 text-right font-medium text-gray-600">Opening
                                        Balance
                                    </td>
                                    <td class="px-3 py-2 text-right font-medium tabular-nums text-gray-900">
                                        {{ number_format($openingBalance, 2) }}
                                    </td>
                                </tr>
                                @forelse ($entries as $entry)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-gray-500">
                                            {{ Carbon::parse($entry->journal_date)->format('M d, Y') }}
                                        </td>
                                        <td class="px-3 py-3 font-medium text-blue-600">{{ $entry->journal_no }}</td>
                                        <td class="px-3 py-3">
                                            <span class="rounded-full bg-gray-50 px-2.5 py-1 text-gray-500">
                                                {{ strtoupper($entry->source_module) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-gray-700">
                                            {{ $entry->description ?: $entry->journal_description }}
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums">
                                            {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums">
                                            {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums font-medium text-gray-900">
                                            {{ number_format($entry->running_balance, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                            No posted activity for this account in the selected period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="border-t-2 border-gray-300 bg-gray-50 font-semibold text-gray-900">
                                <tr>
                                    <td colspan="6" class="px-6 py-3 text-right">Closing Balance</td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ number_format($closingBalance, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
