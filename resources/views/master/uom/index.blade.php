@extends('dashboard')

@section('title', 'Units of Measure')

@section('content')
    <div class="mx-auto max-w-6xl">
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
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                {{ session('error') }}</div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Units of Measure</h1>
                    <p class="mt-1 text-sm text-gray-500">Base units and the units that convert to them (1 BOX = 12 PC).
                    </p>
                </div>
                @if ($canCreate)
                    <a href="{{ route('master.uoms.create') }}"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        + Add Unit
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 border-b px-6 py-4 sm:grid-cols-3">
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="text-xs font-medium text-blue-700">Total Units</div>
                    <div class="mt-1 text-xl font-bold text-blue-900">{{ $totalCount }}</div>
                </div>
                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="text-xs font-medium text-green-700">Base Units</div>
                    <div class="mt-1 text-xl font-bold text-green-900">{{ $baseCount }}</div>
                </div>
                <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                    <div class="text-xs font-medium text-purple-700">Derived Units</div>
                    <div class="mt-1 text-xl font-bold text-purple-900">{{ $derivedCount }}</div>
                </div>
            </div>

            <form method="GET" action="{{ route('master.uom') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <input type="text" name="searchname" value="{{ request('searchname') }}"
                    placeholder="Search by code or name"
                    class="flex-1 min-w-[220px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                <select name="searchtype" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Types</option>
                    <option value="base" @selected(request('searchtype') === 'base')>Base units</option>
                    <option value="derived" @selected(request('searchtype') === 'derived')>Derived units</option>
                </select>
                <select name="searchstatus" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Status</option>
                    <option value="1" @selected(request('searchstatus') === '1')>Active</option>
                    <option value="0" @selected(request('searchstatus') === '0')>Inactive</option>
                </select>
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">Filter</button>
                @if (request()->anyFilled(['searchname', 'searchtype', 'searchstatus']))
                    <a href="{{ route('master.uom') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Code</th>
                            <th class="px-3 py-3 text-left">Name</th>
                            <th class="px-3 py-3 text-left">Type</th>
                            <th class="px-3 py-3 text-left">Conversion</th>
                            <th class="px-3 py-3 text-center">In Use</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            @if ($canUpdate || $canDelete)
                                <th class="px-3 py-3 text-right w-24">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($uoms as $uom)
                            @php
                                $inUse =
                                    $uom->derived_uoms_count +
                                        $uom->items_count +
                                        $uom->purchase_items_count +
                                        $uom->sales_items_count >
                                    0;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-800 {{ $uom->isBase() ? '' : 'pl-10' }}">
                                    {{ $uom->code }}</td>
                                <td class="px-3 py-3 text-gray-700">{{ $uom->name }}</td>
                                <td class="px-3 py-3">
                                    @if ($uom->isBase())
                                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Base</span>
                                    @else
                                        <span class="rounded-full bg-purple-50 px-2.5 py-1 text-purple-700">Derived</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 tabular-nums text-gray-600">{{ $uom->conversionLabel() }}</td>
                                <td class="px-3 py-3 text-center text-gray-500">{{ $inUse ? 'Yes' : '—' }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if ($uom->status)
                                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Active</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-500">Inactive</span>
                                    @endif
                                </td>
                                @if ($canUpdate || $canDelete)
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if ($canUpdate)
                                                <a href="{{ route('master.uoms.edit', $uom) }}" title="Edit Unit"
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
                                                <form method="POST" action="{{ route('master.uoms.destroy', $uom) }}"
                                                    onsubmit="return confirm('Delete unit {{ $uom->code }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Delete Unit"
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
                                <td colspan="{{ $canUpdate || $canDelete ? 7 : 6 }}"
                                    class="px-6 py-12 text-center text-gray-500">No units found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $uoms->firstItem() ?? 0 }}-{{ $uoms->lastItem() ?? 0 }} of {{ $uoms->total() }}</div>
                {{ $uoms->links() }}
            </div>
        </div>
    </div>
@endsection
