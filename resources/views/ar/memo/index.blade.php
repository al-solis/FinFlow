@extends('dashboard')

@section('title', $cfg['plural'])

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
                    <h1 class="text-2xl font-bold text-gray-800">{{ $cfg['plural'] }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $cfg['blurb'] }}</p>
                </div>
                @if ($canCreate)
                    <a href="{{ route($cfg['route'] . '.create') }}"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        + New {{ $cfg['label'] }}
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
                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="text-xs font-medium text-green-700">Posted</div>
                    <div class="mt-1 text-xl font-bold text-green-900">{{ $postedCount }}</div>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="text-xs font-medium text-blue-700">Posted Total</div>
                    <div class="mt-1 text-xl font-bold text-blue-900">₱{{ number_format((float) $postedTotal, 2) }}</div>
                </div>
            </div>

            <form method="GET" action="{{ route($cfg['index_route']) }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <input type="text" name="searchname" value="{{ request('searchname') }}"
                    placeholder="Search number, invoice no., or customer code"
                    class="flex-1 min-w-[220px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                <select name="searchapproval" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Status</option>
                    @foreach (['0' => 'Draft', '1' => 'Pending Approval', '2' => 'Approved', '3' => 'Rejected', '4' => 'Returned'] as $v => $t)
                        <option value="{{ $v }}" @selected(request('searchapproval') === (string) $v)>{{ $t }}</option>
                    @endforeach
                </select>
                <input type="date" name="datefrom" value="{{ request('datefrom') }}"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs" title="Date from">
                <input type="date" name="dateto" value="{{ request('dateto') }}"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs" title="Date to">
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">Filter</button>
                @if (request()->anyFilled(['searchname', 'searchapproval', 'datefrom', 'dateto']))
                    <a href="{{ route($cfg['index_route']) }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">{{ $cfg['label'] }} No.</th>
                            <th class="px-3 py-3 text-left">Date</th>
                            <th class="px-3 py-3 text-left">Customer</th>
                            <th class="px-3 py-3 text-left">Invoice</th>
                            <th class="px-3 py-3 text-left">Reason</th>
                            <th class="px-3 py-3 text-right">Total</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($memos as $m)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium">
                                    <a href="{{ route($cfg['route'] . '.show', $m) }}"
                                        class="text-blue-600 hover:underline">{{ $m->displayNo() }}</a>
                                </td>
                                <td class="px-3 py-3 text-gray-600 whitespace-nowrap">
                                    {{ $m->{$cfg['date_col']}?->format('M d, Y') }}</td>
                                <td class="px-3 py-3 text-gray-700">{{ $m->customer_display }}</td>
                                <td class="px-3 py-3 text-gray-600">{{ $m->originalInvoice?->invoice_no ?? '—' }}</td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $cfg['reasons'][$m->reason_code] ?? '—' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-800">
                                    {{ number_format((float) $m->total_amount, 2) }}</td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full {{ $m->statusBadgeClass() }} px-2.5 py-1 text-[10px] font-medium">{{ $m->statusLabel() }}</span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <a href="{{ route($cfg['route'] . '.show', $m) }}"
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">No
                                    {{ strtolower($cfg['plural']) }} found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $memos->firstItem() ?? 0 }}-{{ $memos->lastItem() ?? 0 }} of {{ $memos->total() }}</div>
                {{ $memos->links() }}
            </div>
        </div>
    </div>
@endsection
