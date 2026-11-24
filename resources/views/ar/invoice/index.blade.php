@extends('dashboard')

@section('title', 'Sales Invoices')

@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                {{ session('error') }}</div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Sales Invoices</h1>
                    <p class="mt-1 text-sm text-gray-500">Bill customers, track approval, and monitor what is still
                        unpaid.</p>
                </div>
                @if ($canCreate)
                    <a href="{{ route('ar.invoices.create') }}"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        + New Invoice
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 border-b px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs font-medium text-gray-500">Drafts / Returned</div>
                    <div class="mt-1 text-xl font-bold text-gray-900">{{ $draftCount }}</div>
                </div>
                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                    <div class="text-xs font-medium text-yellow-700">Pending Approval</div>
                    <div class="mt-1 text-xl font-bold text-yellow-900">{{ $pendingCount }}</div>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="text-xs font-medium text-blue-700">Open Receivables</div>
                    <div class="mt-1 text-xl font-bold text-blue-900">₱{{ number_format($openAmount, 2) }}</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <div class="text-xs font-medium text-red-700">Overdue</div>
                    <div class="mt-1 text-xl font-bold text-red-900">₱{{ number_format($overdueAmount, 2) }}</div>
                </div>
            </div>

            <form method="GET" action="{{ route('ar.inv') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <input type="text" name="searchname" value="{{ request('searchname') }}"
                    placeholder="Search invoice no., customer code, or PO no."
                    class="flex-1 min-w-[220px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                <select name="searchapproval" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Approval Status</option>
                    @foreach (['0' => 'Draft', '1' => 'Pending Approval', '2' => 'Approved', '3' => 'Rejected', '4' => 'Returned'] as $v => $t)
                        <option value="{{ $v }}" @selected(request('searchapproval') === (string) $v)>{{ $t }}</option>
                    @endforeach
                </select>
                <select name="searchpayment" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Payment Status</option>
                    @foreach (['unpaid' => 'Unpaid', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'overdue' => 'Overdue'] as $v => $t)
                        <option value="{{ $v }}" @selected(request('searchpayment') === $v)>{{ $t }}</option>
                    @endforeach
                </select>
                <input type="date" name="datefrom" value="{{ request('datefrom') }}"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs" title="Invoice date from">
                <input type="date" name="dateto" value="{{ request('dateto') }}"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs" title="Invoice date to">
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">Filter</button>
                @if (request()->anyFilled(['searchname', 'searchapproval', 'searchpayment', 'datefrom', 'dateto']))
                    <a href="{{ route('ar.inv') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Invoice No.</th>
                            <th class="px-3 py-3 text-left">Date</th>
                            <th class="px-3 py-3 text-left">Customer</th>
                            <th class="px-3 py-3 text-left">Due</th>
                            <th class="px-3 py-3 text-right">Total</th>
                            <th class="px-3 py-3 text-right">Balance</th>
                            <th class="px-3 py-3 text-center">Approval</th>
                            <th class="px-3 py-3 text-center">Payment</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($invoices as $inv)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium">
                                    <a href="{{ route('ar.invoices.show', $inv) }}"
                                        class="text-blue-600 hover:underline">{{ $inv->displayNo() }}</a>
                                </td>
                                <td class="px-3 py-3 text-gray-600 whitespace-nowrap">
                                    {{ $inv->invoice_date->format('M d, Y') }}
                                </td>
                                <td class="px-3 py-3 text-gray-700">{{ $inv->customer_display }}</td>
                                <td
                                    class="px-3 py-3 whitespace-nowrap {{ $inv->isOverdue() ? 'font-semibold text-red-600' : 'text-gray-600' }}">
                                    {{ $inv->due_date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-800">
                                    {{ number_format(bcadd($inv->total_amount ?? 0, $inv->debit_applied ?? 0, 2), 2) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums font-medium text-gray-800">
                                    {{ $inv->approval_status === '2' ? number_format($inv->amount_due, 2) : '—' }}</td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full {{ $inv->statusBadgeClass() }} px-2.5 py-1 text-[10px] font-medium">{{ $inv->statusLabel() }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full {{ $inv->paymentStatusBadgeClass() }} px-2.5 py-1 text-[10px] font-medium">{{ $inv->paymentStatusLabel() }}</span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <a href="{{ route('ar.invoices.show', $inv) }}"
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $invoices->firstItem() ?? 0 }}-{{ $invoices->lastItem() ?? 0 }} of
                    {{ $invoices->total() }}</div>
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
@endsection
