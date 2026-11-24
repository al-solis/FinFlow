@extends('dashboard')

@section('title', 'Items')

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
                class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 shadow-sm transition-all duration-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-4 w-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
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
                    <h1 class="text-2xl font-bold text-gray-800">Items</h1>
                    <p class="mt-1 text-sm text-gray-500">Items and services picked on Requests for Disbursement and Sales
                        Invoices.</p>
                </div>
                @if ($canCreate)
                    <a href="{{ route('master.items.create') }}"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        + Add Item
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 border-b px-6 py-4 sm:grid-cols-3">
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="text-xs font-medium text-blue-700">Total Items</div>
                    <div class="mt-1 text-xl font-bold text-blue-900">{{ $totalCount }}</div>
                </div>
                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="text-xs font-medium text-green-700">Active</div>
                    <div class="mt-1 text-xl font-bold text-green-900">{{ $activeCount }}</div>
                </div>
                <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                    <div class="text-xs font-medium text-purple-700">Sellable</div>
                    <div class="mt-1 text-xl font-bold text-purple-900">{{ $sellableCount }}</div>
                </div>
            </div>

            <form method="GET" action="{{ route('master.item') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <input type="text" name="searchname" value="{{ request('searchname') }}"
                    placeholder="Search by code, name, or description"
                    class="flex-1 min-w-[220px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                <select name="searchsellable" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">Purchase &amp; Sales</option>
                    <option value="1" @selected(request('searchsellable') === '1')>Sellable only</option>
                    <option value="0" @selected(request('searchsellable') === '0')>Purchase only</option>
                </select>
                <select name="searchstatus" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Status</option>
                    <option value="1" @selected(request('searchstatus') === '1')>Active</option>
                    <option value="0" @selected(request('searchstatus') === '0')>Inactive</option>
                </select>
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">Filter</button>
                @if (request()->anyFilled(['searchname', 'searchstatus', 'searchsellable']))
                    <a href="{{ route('master.item') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Item</th>
                            <th class="px-3 py-3 text-left">UOM</th>
                            <th class="px-3 py-3 text-left">Purchase GL</th>
                            <th class="px-3 py-3 text-right">Purchase Price</th>
                            <th class="px-3 py-3 text-left">Sales GL</th>
                            <th class="px-3 py-3 text-right">Sales Price</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            @if ($canUpdate || $canDelete)
                                <th class="px-3 py-3 text-right w-24">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <div class="font-medium text-gray-800">{{ $item->name }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $item->code }}</div>
                                </td>
                                <td class="px-3 py-3 text-gray-600">{{ $item->unit_of_measure->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $item->glAccount?->getFormattedAccountCodeAttribute() ?? '—' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-700">
                                    {{ $item->default_unit_price !== null ? number_format((float) $item->default_unit_price, 2) : '—' }}
                                </td>
                                <td class="px-3 py-3 text-gray-600">
                                    @if ($item->is_sellable)
                                        {{ $item->salesGlAccount?->getFormattedAccountCodeAttribute() ?? '—' }}
                                    @else
                                        <span class="text-gray-300">Not sellable</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-700">
                                    {{ $item->is_sellable && $item->default_sales_price !== null ? number_format((float) $item->default_sales_price, 2) : '—' }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($item->status)
                                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Active</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-500">Inactive</span>
                                    @endif
                                </td>
                                @if ($canUpdate || $canDelete)
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if ($canUpdate)
                                                <a href="{{ route('master.items.edit', $item) }}" title="Edit Item"
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
                                            @if ($canDelete)
                                                <form method="POST" action="{{ route('master.items.destroy', $item) }}"
                                                    onsubmit="return confirm('Delete item {{ $item->code }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Delete Item"
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
                                <td colspan="{{ $canUpdate || $canDelete ? 8 : 7 }}"
                                    class="px-6 py-12 text-center text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} of {{ $items->total() }}
                </div>
                {{ $items->links() }}
            </div>
        </div>
    </div>
@endsection
