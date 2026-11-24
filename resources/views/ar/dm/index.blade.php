@extends('dashboard')

@section('title', 'Debit Memos')

@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Debit Memos</h1>
                    <p class="mt-1 text-sm text-gray-500">Bill additional charges, underbillings, or adjustments that
                        increase customer receivables.</p>
                </div>
                @if ($canCreate ?? true)
                    <a href="{{ route('ar.dm.create') }}"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        + New Debit Memo
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 border-b px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs font-medium text-gray-500">Drafts / Returned</div>
                    <div class="mt-1 text-xl font-bold text-gray-900">{{ $draftCount ?? 0 }}</div>
                </div>
                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                    <div class="text-xs font-medium text-yellow-700">Pending Approval</div>
                    <div class="mt-1 text-xl font-bold text-yellow-900">{{ $pendingCount ?? 0 }}</div>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="text-xs font-medium text-blue-700">Posted Debit Memos</div>
                    <div class="mt-1 text-xl font-bold text-blue-900">₱{{ number_format((float) ($postedAmount ?? 0), 2) }}
                    </div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <div class="text-xs font-medium text-red-700">Open Balance</div>
                    <div class="mt-1 text-xl font-bold text-red-900">₱{{ number_format((float) ($openAmount ?? 0), 2) }}
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('ar.dmemo') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <input type="text" name="searchname" value="{{ request('searchname') }}"
                    placeholder="Search DM no., reference invoice, or customer"
                    class="flex-1 min-w-[220px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                <select name="searchapproval" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Approval Status</option>
                    @foreach (['0' => 'Draft', '1' => 'Pending Approval', '2' => 'Approved', '3' => 'Rejected', '4' => 'Returned'] as $v => $t)
                        <option value="{{ $v }}" @selected(request('searchapproval') === (string) $v)>{{ $t }}</option>
                    @endforeach
                </select>
                <input type="date" name="datefrom" value="{{ request('datefrom') }}"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                <input type="date" name="dateto" value="{{ request('dateto') }}"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">Filter</button>
                @if (request()->anyFilled(['searchname', 'searchapproval', 'datefrom', 'dateto']))
                    <a href="{{ route('ar.dm') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">DM No.</th>
                            <th class="px-3 py-3 text-left">Date</th>
                            <th class="px-3 py-3 text-left">Customer</th>
                            <th class="px-3 py-3 text-left">Reference Invoice</th>
                            <th class="px-3 py-3 text-right">Total</th>
                            <th class="px-3 py-3 text-right">Balance Due</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($debitMemos as $dm)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium">
                                    <a href="{{ route('ar.dm.show', $dm) }}"
                                        class="text-blue-600 hover:underline">{{ method_exists($dm, 'displayNo') ? $dm->displayNo() : $dm->debit_memo_no ?? 'DM #' . $dm->id }}</a>
                                </td>
                                <td class="px-3 py-3 text-gray-600 whitespace-nowrap">
                                    {{ optional($dm->memo_date ?? $dm->debit_memo_date)->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-gray-700">
                                    {{ $dm->customer_display ?? ($dm->customer->name ?? '—') }}</td>
                                <td class="px-3 py-3 text-gray-600">
                                    @if ($dm->invoice)
                                        <a href="{{ route('ar.invoices.show', $dm->invoice) }}"
                                            class="text-blue-600 hover:underline">
                                            {{ method_exists($dm->invoice, 'displayNo') ? $dm->invoice->displayNo() : $dm->invoice->invoice_no ?? 'INV #' . $dm->invoice->id }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-800">
                                    {{ number_format((float) ($dm->total_amount ?? 0), 2) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums font-medium text-gray-800">
                                    {{ ($dm->approval_status ?? '') === '2' ? number_format((float) ($dm->amount_unapplied ?? ($dm->total_amount ?? 0)), 2) : '—' }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full {{ method_exists($dm, 'statusBadgeClass') ? $dm->statusBadgeClass() : 'bg-gray-100 text-gray-700' }} px-2.5 py-1 text-[10px] font-medium">
                                        {{ method_exists($dm, 'statusLabel') ? $dm->statusLabel() : ucfirst((string) ($dm->approval_status ?? 'Draft')) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <a href="{{ route('ar.dm.show', $dm) }}"
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">No debit memos found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $debitMemos->firstItem() ?? 0 }}-{{ $debitMemos->lastItem() ?? 0 }} of
                    {{ $debitMemos->total() }}</div>
                {{ $debitMemos->links() }}
            </div>
        </div>
    </div>
@endsection
