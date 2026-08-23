@extends('dashboard')

@section('title', 'Review Statement Import')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200 p-4" x-data="reconciliationReview()">
            {{-- @if (session('success'))
                <div class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-xs text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif --}}

            @if (session('success'))
                <div id="success-alert"
                    class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800 shadow-sm transition-all duration-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="h-4 w-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ session('success') }}
                        </div>
                        <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                            class="text-green-600 hover:text-green-800 transition-colors duration-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div id="error-alert"
                    class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 shadow-sm transition-all duration-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="h-4 w-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </div>
                        <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                            class="text-red-600 hover:text-red-800 transition-colors duration-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $import->original_filename }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $import->bankAccount->name ?? '—' }} — {{ $import->bankAccount->account_number ?? '' }}
                        @if ($import->statement_from && $import->statement_to)
                            &middot; {{ $import->statement_from->format('M d, Y') }} –
                            {{ $import->statement_to->format('M d, Y') }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('bm.recon') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    ← Back to Imports
                </a>
            </div>

            <form method="POST" action="{{ route('bm.recon.post', $import->id) }}">
                @csrf

                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">
                                        <input type="checkbox" checked @change="toggleAll($event.target.checked)">
                                    </th>
                                    <th class="px-3 py-3 text-left">Date</th>
                                    <th class="px-3 py-3 text-left">Description</th>
                                    <th class="px-3 py-3 text-left">Payee</th>
                                    <th class="px-3 py-3 text-left">Reference / Check #</th>
                                    <th class="px-3 py-3 text-right">Amount</th>
                                    <th class="px-3 py-3 text-left min-w-[220px]">Offsetting GL Account</th>
                                    <th class="px-3 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($import->lines as $line)
                                    <tr class="hover:bg-gray-50 {{ $line->status !== 'pending' ? 'opacity-60' : '' }}">
                                        <td class="px-4 py-3 text-center">
                                            <input type="checkbox" name="accepted[{{ $line->id }}]" value="1"
                                                x-model="accepted[{{ $line->id }}]"
                                                {{ $line->status !== 'pending' ? 'disabled' : '' }}
                                                {{ $line->status === 'skipped' ? '' : 'checked' }}>
                                        </td>
                                        <td class="px-3 py-3 text-gray-500 whitespace-nowrap">
                                            {{ $line->transaction_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-3 py-3 text-gray-800">{{ $line->description ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-700">{{ $line->payee ?: '—' }}</td>
                                        <td class="px-3 py-3 text-gray-500">
                                            {{ $line->reference ?: '—' }}
                                            @if ($line->check_number)
                                                <span class="block text-gray-400">Check #{{ $line->check_number }}</span>
                                            @endif
                                        </td>
                                        <td
                                            class="px-3 py-3 text-right tabular-nums font-medium {{ $line->isDeposit() ? 'text-green-700' : 'text-red-700' }}">
                                            {{ $line->isDeposit() ? '+' : '' }}{{ number_format($line->amount, 2) }}
                                        </td>
                                        <td class="px-3 py-3">
                                            @if ($line->status === 'posted')
                                                <span class="text-gray-700">
                                                    {{ $line->glAccount->account_code ?? '—' }}
                                                    {{ $line->glAccount ? '— ' . $line->glAccount->account_name : '' }}
                                                </span>
                                            @else
                                                <select name="gl_account_id[{{ $line->id }}]"
                                                    :disabled="!accepted[{{ $line->id }}]"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs disabled:bg-gray-100">
                                                    <option value="">Select account&hellip;</option>
                                                    @foreach ($glAccounts as $account)
                                                        <option value="{{ $account->id }}" @selected($line->gl_account_id == $account->id)>
                                                            {{ $account->account_code }} &mdash;
                                                            {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            @if ($line->status === 'posted')
                                                <span
                                                    class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Posted</span>
                                            @elseif ($line->status === 'skipped')
                                                <span
                                                    class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-500">Skipped</span>
                                            @else
                                                <span
                                                    class="rounded-full bg-yellow-50 px-2.5 py-1 text-yellow-700">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            No line items in this import.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($import->lines->where('status', 'pending')->isNotEmpty())
                        <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <p class="text-xs text-gray-500">
                                Unchecked lines will be skipped (left unposted). Checked lines require an offsetting GL
                                account before posting.
                            </p>
                            <button type="submit"
                                class="rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">
                                Post Accepted Lines
                            </button>
                        </div>
                    @else
                        <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 text-center text-xs text-gray-500">
                            All lines in this import have been posted or skipped.
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>


    <script>
        function reconciliationReview() {
            return {
                accepted: {
                    @foreach ($import->lines as $line)
                        {{ $line->id }}: {{ $line->status === 'skipped' ? 'false' : 'true' }},
                    @endforeach
                },
                toggleAll(checked) {
                    Object.keys(this.accepted).forEach(id => {
                        this.accepted[id] = checked;
                    });
                },
            };
        }
    </script>
@endsection
