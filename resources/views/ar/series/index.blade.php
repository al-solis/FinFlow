@extends('dashboard')

@section('title', 'Invoice Series')

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
                    <h1 class="text-2xl font-bold text-gray-800">Invoice Series</h1>
                    <p class="mt-1 text-sm text-gray-500">BIR-registered number ranges. A number is issued only when a
                        document is approved, so the sequence has no gaps.</p>
                </div>
                @if ($canCreate)
                    <a href="{{ route('ar.series.create') }}"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        + Add Series
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 border-b px-6 py-4 sm:grid-cols-3">
                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="text-xs font-medium text-green-700">Active Series</div>
                    <div class="mt-1 text-xl font-bold text-green-900">{{ $activeCount }}</div>
                </div>
                <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
                    <div class="text-xs font-medium text-orange-700">Expired / Expiring in 30 days</div>
                    <div class="mt-1 text-xl font-bold text-orange-900">{{ $expiringCount }}</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <div class="text-xs font-medium text-red-700">Running Low</div>
                    <div class="mt-1 text-xl font-bold text-red-900">{{ $lowCount }}</div>
                </div>
            </div>

            <form method="GET" action="{{ route('ar.ser') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <select name="searchtype" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Document Types</option>
                    @foreach ($types as $value => $text)
                        <option value="{{ $value }}" @selected(request('searchtype') === $value)>{{ $text }}</option>
                    @endforeach
                </select>
                <select name="searchstatus" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Status</option>
                    <option value="1" @selected(request('searchstatus') === '1')>Active</option>
                    <option value="0" @selected(request('searchstatus') === '0')>Inactive</option>
                </select>
                @if (request()->anyFilled(['searchtype', 'searchstatus']))
                    <a href="{{ route('ar.ser') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Series</th>
                            <th class="px-3 py-3 text-left">Document</th>
                            <th class="px-3 py-3 text-left">Range</th>
                            <th class="px-3 py-3 text-left">Next Number</th>
                            <th class="px-3 py-3 text-right">Remaining</th>
                            <th class="px-3 py-3 text-left">BIR Authority</th>
                            <th class="px-3 py-3 text-left">Valid Until</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            @if ($canUpdate || $canDelete)
                                <th class="px-3 py-3 text-right w-24">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($series as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <div class="font-medium text-gray-800">{{ $row->code }}</div>
                                    @if ($row->is_default)
                                        <span
                                            class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] text-blue-700">Default</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $types[$row->document_type] ?? $row->document_type }}
                                </td>
                                <td class="px-3 py-3 tabular-nums text-gray-600">
                                    {{ $row->label((int) $row->series_start) }}
                                    –
                                    {{ $row->series_end ? $row->label((int) $row->series_end) : 'no end' }}
                                </td>
                                <td class="px-3 py-3 font-medium tabular-nums text-gray-800">{{ $row->nextLabel() }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">
                                    @if ($row->remaining() === null)
                                        <span class="text-gray-400">—</span>
                                    @else
                                        <span class="{{ $row->isLow() ? 'font-semibold text-red-600' : 'text-gray-700' }}">
                                            {{ number_format($row->remaining()) }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-gray-600">{{ $row->bir_authority_no ?: '—' }}</td>
                                <td class="px-3 py-3">
                                    @if ($row->valid_until)
                                        <span
                                            class="{{ $row->isExpired() ? 'font-semibold text-red-600' : ($row->expiresWithin(30) ? 'font-semibold text-orange-600' : 'text-gray-600') }}">
                                            {{ $row->valid_until->format('M d, Y') }}
                                            @if ($row->isExpired())
                                                (expired)
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    {{-- FIX: a locked series is shown as "Locked" so it is obvious at a glance that it cannot be edited semantically, only extended. --}}
                                    @if ($row->isLocked())
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700"
                                            title="Numbers have been issued from this series">Locked</span>
                                    @elseif ($row->is_active)
                                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Active</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-500">Inactive</span>
                                    @endif
                                </td>
                                @if ($canUpdate || $canDelete)
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if ($canUpdate)
                                                <a href="{{ route('ar.series.edit', $row) }}" title="Edit Series"
                                                    class="text-gray-500 hover:text-blue-600 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293a.5.5 0 0 1 0 .707z" />
                                                        <path
                                                            d="M13.752 4.396l-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12z" />
                                                        <path fill-rule="evenodd"
                                                            d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                                                    </svg>
                                                </a>
                                            @endif
                                            @if ($canDelete && !$row->isLocked())
                                                <form method="POST" action="{{ route('ar.series.destroy', $row) }}"
                                                    onsubmit="return confirm('Delete series {{ $row->code }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Delete Series"
                                                        class="text-gray-500 hover:text-red-600 transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                            height="16" fill="currentColor" viewBox="0 0 16 16">
                                                            <path
                                                                d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 1 0" />
                                                            <path
                                                                d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canUpdate || $canDelete ? 9 : 8 }}"
                                    class="px-6 py-12 text-center text-gray-500">
                                    No series yet. Add the series from your BIR Authority to Print / Permit to Use
                                    before issuing invoices.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $series->firstItem() ?? 0 }}-{{ $series->lastItem() ?? 0 }} of {{ $series->total() }}
                </div>
                {{ $series->links() }}
            </div>
        </div>
    </div>
@endsection
