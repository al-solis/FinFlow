@extends('dashboard')
@section('title', 'Bank Reconciliation')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @if (session('success'))
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
            @endif

            <div class="mb-5">
                <h1 class="text-2xl font-bold text-gray-800">Bank Reconciliation</h1>
                <p class="mt-1 text-sm text-gray-500">Import a bank statement CSV and post unrecorded transactions</p>
            </div>

            <!-- Upload -->
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700">Import Statement</h2>

                <form method="POST" action="{{ route('bm.recon.upload') }}" enctype="multipart/form-data"
                    class="flex flex-wrap items-end gap-3">
                    @csrf

                    <div class="min-w-[240px]">
                        <label class="block text-xs font-medium text-gray-900 mb-1">
                            Bank Account <span class="text-red-500">*</span>
                        </label>
                        <select name="bank_account_id" required
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                            <option value="">— Select —</option>
                            @foreach ($bankAccounts as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }} — {{ $bank->account_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="min-w-[280px] flex-1">
                        <label class="block text-xs font-medium text-gray-900 mb-1">
                            Statement CSV <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="statement_file" accept=".csv,.txt" required
                            class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-1 text-[11px] text-gray-400">
                            Expected columns: Date, Amount, Payee, Description, Reference, Check Number.
                            Positive amount = deposit, negative = withdrawal/charge.
                        </p>
                    </div>

                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        Upload &amp; Review
                    </button>
                </form>
            </div>

            <!-- Import history -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-700">Import History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                            <tr>
                                <th class="px-6 py-3 text-left">File</th>
                                <th class="px-3 py-3 text-left">Bank Account</th>
                                <th class="px-3 py-3 text-left">Statement Period</th>
                                <th class="px-3 py-3 text-right">Lines</th>
                                <th class="px-3 py-3 text-center">Status</th>
                                <th class="px-3 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($imports as $import)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-gray-800">{{ $import->original_filename }}</td>
                                    <td class="px-3 py-3 text-gray-700">{{ $import->bankAccount->name ?? '—' }}</td>
                                    <td class="px-3 py-3 text-gray-500">
                                        @if ($import->statement_from && $import->statement_to)
                                            {{ $import->statement_from->format('M d, Y') }} –
                                            {{ $import->statement_to->format('M d, Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ $import->total_lines }}</td>
                                    <td class="px-3 py-3 text-center">
                                        @if ($import->status === 'posted')
                                            <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Posted</span>
                                        @else
                                            <span class="rounded-full bg-yellow-50 px-2.5 py-1 text-yellow-700">Pending
                                                Review</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('bm.recon.review', $import->id) }}"
                                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                                            {{ $import->status === 'posted' ? 'View' : 'Review' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        No statement imports yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                    <div>Showing {{ $imports->firstItem() ?? 0 }}-{{ $imports->lastItem() ?? 0 }} of
                        {{ $imports->total() }}
                    </div>
                    {{ $imports->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
