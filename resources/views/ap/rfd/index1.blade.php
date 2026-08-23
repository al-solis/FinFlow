@extends('dashboard')

@section('title', 'Requests for Disbursement')

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
                        class="text-green-600 hover:text-green-800 transition-colors duration-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <!-- Header -->
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Requests for Disbursement</h1>
                    <p class="mt-1 text-sm text-gray-500">Create and track disbursement requests through approval.</p>
                </div>

                <a href="{{ route('ap.rfd.create') }}"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    + New Request
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('ap.rfd') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="searchvendor" value="{{ request('searchvendor') }}"
                        placeholder="Search by vendor (any line)"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-xs">
                </div>

                <div>
                    <select name="searchapproval" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">All Approval Status</option>
                        <option value="draft" @selected(request('searchapproval') === '0')>Draft</option>
                        <option value="pending" @selected(request('searchapproval') === '1')>Pending Approval</option>
                        <option value="returned" @selected(request('searchapproval') === '4')>Returned</option>
                        <option value="approved" @selected(request('searchapproval') === '2')>Approved</option>
                        <option value="rejected" @selected(request('searchapproval') === '3')>Rejected</option>
                    </select>
                </div>

                <div>
                    <input type="date" name="datefrom" value="{{ request('datefrom') }}"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                </div>
                <div>
                    <input type="date" name="dateto" value="{{ request('dateto') }}"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                </div>

                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    Filter
                </button>

                @if (request()->anyFilled(['searchvendor', 'searchapproval', 'datefrom', 'dateto']))
                    <a href="{{ route('ap.rfd') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">@include('partials.sort-link', ['field' => 'id', 'label' => 'RFD #'])</th>
                            <th class="px-3 py-3 text-left">Vendor(s)</th>
                            <th class="px-3 py-3 text-left">Request Date</th>
                            <th class="px-3 py-3 text-right">Total Due</th>
                            <th class="px-3 py-3 text-center">Approval Status</th>
                            <th class="px-3 py-3 text-right w-20">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rfds as $rfd)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-800">
                                    RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-3 py-3 text-gray-700">{{ $rfd->vendorSummary() }}</td>
                                <td class="px-3 py-3 text-gray-500">
                                    {{ $rfd->request_date?->format('M d, Y') }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-700">
                                    {{ $rfd->currency->code ?? '' }} {{ number_format($rfd->total_due, 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full {{ $rfd->statusBadgeClass() }} px-2.5 py-1 text-xs font-medium">
                                        {{ $rfd->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if (in_array($rfd->approval_status, ['0', '4']))
                                        <a href="{{ route('ap.rfd.edit', $rfd) }}" title="Edit RFD"
                                            class="text-gray-500 hover:text-blue-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                <path
                                                    d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                                                <path fill-rule="evenodd"
                                                    d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                                            </svg>
                                        </a>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <img src="{{ asset('images/rfd.svg') }}" alt="No requests for disbursement"
                                        class="mx-auto mb-4 h-24 w-24">
                                    No requests for disbursement found. Click
                                    <a href="{{ route('ap.rfd.create') }}" class="text-blue-600 hover:underline">here</a>
                                    to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>
                    Showing {{ $rfds->firstItem() ?? 0 }}-{{ $rfds->lastItem() ?? 0 }} of {{ $rfds->total() }}
                </div>
                {{ $rfds->links() }}
            </div>
        </div>
    </div>
@endsection
