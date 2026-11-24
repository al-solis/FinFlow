@extends('dashboard')

@section('title', 'Collections')

@section('content')
    <div class="mx-auto max-w-7xl">
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
                        class="text-green-600 hover:text-green-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div id="error-alert"
                class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-4 w-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.334-.213 2.98-1.742 2.98H3.48c-1.53 0-2.492-1.646-1.743-2.98l6.518-11.59zM10 13a1 1 0 100-2 1 1 0 000 2zm-1-8a1 1 0 011 1v3a1 1 0 11-2 0V6a1 1 0 011-1z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ session('error') }}
                    </div>
                    <button type="button" onclick="this.closest('[id$=-alert]').style.display='none'"
                        class="text-red-600 hover:text-red-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Collections</h1>
                    <p class="mt-1 text-sm text-gray-500">Record customer payments, apply them to invoices, and track
                        unapplied cash.</p>
                </div>
                @if ($canCreate)
                    <a href="{{ route('ar.collections.create') }}"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        + New Collection
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 border-b px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs font-medium text-gray-500">Drafts</div>
                    <div class="mt-1 text-xl font-bold text-gray-900">{{ $draftCount }}</div>
                </div>
                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="text-xs font-medium text-green-700">Posted</div>
                    <div class="mt-1 text-xl font-bold text-green-900">{{ $postedCount }}</div>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="text-xs font-medium text-blue-700">Total Received</div>
                    <div class="mt-1 text-xl font-bold text-blue-900">₱{{ number_format($totalReceived, 2) }}</div>
                </div>
                <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
                    <div class="text-xs font-medium text-orange-700">Unapplied Cash</div>
                    <div class="mt-1 text-xl font-bold text-orange-900">₱{{ number_format($unappliedTotal, 2) }}</div>
                </div>
            </div>

            <form method="GET" action="{{ route('ar.col') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <input type="text" name="searchname" value="{{ request('searchname') }}"
                    placeholder="Search collection no., reference, check no., customer"
                    class="flex-1 min-w-[220px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                <select name="searchapproval" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Approval Status</option>
                    @foreach (['0' => 'Draft', '1' => 'Pending Approval', '2' => 'Approved', '3' => 'Rejected', '4' => 'Returned'] as $v => $t)
                        <option value="{{ $v }}" @selected(request('searchapproval') === (string) $v)>{{ $t }}</option>
                    @endforeach
                </select>
                <select name="searchstatus" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Status</option>
                    @foreach (['draft' => 'Draft', 'posted' => 'Posted', 'bounced' => 'Bounced', 'voided' => 'Voided'] as $v => $t)
                        <option value="{{ $v }}" @selected(request('searchstatus') === $v)>{{ $t }}</option>
                    @endforeach
                </select>
                <input type="date" name="datefrom" value="{{ request('datefrom') }}"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs" title="From date">
                <input type="date" name="dateto" value="{{ request('dateto') }}"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs" title="To date">
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">Filter</button>
                @if (request()->anyFilled(['searchname', 'searchstatus', 'datefrom', 'dateto']))
                    <a href="{{ route('ar.collections.index') }}"
                        class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Collection No.</th>
                            <th class="px-3 py-3 text-left">Date</th>
                            <th class="px-3 py-3 text-left">Customer</th>
                            <th class="px-3 py-3 text-left">Reference</th>
                            <th class="px-3 py-3 text-right">Received</th>
                            <th class="px-3 py-3 text-right">Applied</th>
                            <th class="px-3 py-3 text-right">Unapplied</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($collections as $c)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium">
                                    <a href="{{ route('ar.collections.show', $c) }}"
                                        class="text-blue-600 hover:underline">{{ $c->displayNo() }}</a>
                                </td>
                                <td class="px-3 py-3 text-gray-600 whitespace-nowrap">
                                    {{ $c->collection_date->format('M d, Y') }}</td>
                                <td class="px-3 py-3 text-gray-700">{{ $c->customer->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $c->reference_number ?: $c->check_number ?: '—' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-800">
                                    {{ number_format((float) $c->amount_received, 2) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-700">
                                    {{ number_format((float) $c->amount_applied, 2) }}</td>
                                <td
                                    class="px-3 py-3 text-right tabular-nums {{ (float) $c->amount_unapplied > 0 ? 'font-medium text-orange-700' : 'text-gray-700' }}">
                                    {{ number_format((float) $c->amount_unapplied, 2) }}</td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span
                                            class="inline-flex rounded-full {{ $c->approvalStatusBadgeClass() }} px-2.5 py-1 text-[10px] font-medium">
                                            {{ $c->approvalStatusLabel() }}
                                        </span>
                                        @if ($c->approval_status === '2')
                                            <span
                                                class="inline-flex rounded-full {{ $c->settlementStatusBadgeClass() }} px-2.5 py-1 text-[10px] font-medium">
                                                {{ $c->settlementStatusLabel() }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <a href="{{ route('ar.collections.show', $c) }}"
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">No collections found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $collections->firstItem() ?? 0 }}-{{ $collections->lastItem() ?? 0 }} of
                    {{ $collections->total() }}</div>
                {{ $collections->links() }}
            </div>
        </div>
    </div>
@endsection
