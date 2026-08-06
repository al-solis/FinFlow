@extends('dashboard')
@section('title', 'Vendor Master')
@section('content')
    <div class="mx-auto max-w-7xl">

        @if (session('success'))
            <div id="alert-message"
                class="mt-5 mb-5 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <!-- Header -->
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Vendor Master</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage supplier records used across the Accounts Payable module.
                    </p>
                </div>

                <a href="{{ route('ap.vendors.create') }}"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    + Add Vendor
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('ap.vendors') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <div class="relative flex-1 min-w-[240px]">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by code, name, or TIN"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-xs">
                </div>

                <select name="category" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Categories</option>
                    @foreach ($vendorCategories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                            {{ $category->code }} - {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Status</option>
                    <option value="1" @selected(request('status') === '1')>Active</option>
                    <option value="0" @selected(request('status') === '0')>Inactive</option>
                </select>

                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    Filter
                </button>

                @if (request()->anyFilled(['search', 'category', 'status']))
                    <a href="{{ route('ap.vendors') }}" class="text-xs text-gray-500 hover:text-gray-700">
                        Clear
                    </a>
                @endif
            </form>

            <!-- Bulk action bar -->
            <div class="flex items-center justify-between px-6 py-3 text-xs text-gray-500" id="bulkBar">
                <span id="selectedCount">0 vendors selected</span>
                <div class="flex gap-4 opacity-50 pointer-events-none" id="bulkActions">
                    <button type="button" class="font-medium text-gray-600 hover:text-gray-800">Activate</button>
                    <button type="button" class="font-medium text-gray-600 hover:text-gray-800">Deactivate</button>
                    <button type="button" class="font-medium text-red-600 hover:text-red-700">Delete</button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left w-10">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                            </th>
                            <th class="px-3 py-3 text-left">
                                @include('partials.sort-link', [
                                    'field' => 'name',
                                    'label' => 'Vendor',
                                ])
                            </th>
                            <th class="px-3 py-3 text-left">
                                @include('partials.sort-link', [
                                    'field' => 'vendor_category_id',
                                    'label' => 'Category',
                                ])
                            </th>
                            <th class="px-3 py-3 text-left">
                                @include('partials.sort-link', [
                                    'field' => 'contact_person',
                                    'label' => 'Contact',
                                ])
                            </th>
                            <th class="px-3 py-3 text-left">Payment Term</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right w-16">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($vendors as $vendor)
                            @php
                                $colors = [
                                    'bg-red-100 text-red-700',
                                    'bg-blue-100 text-blue-700',
                                    'bg-green-100 text-green-700',
                                    'bg-yellow-100 text-yellow-700',
                                    'bg-purple-100 text-purple-700',
                                    'bg-pink-100 text-pink-700',
                                ];
                                $initials = collect(explode(' ', $vendor->name))
                                    ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                                    ->take(2)
                                    ->implode('');
                                $color = $colors[$vendor->id % count($colors)];
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <input type="checkbox" class="row-checkbox rounded border-gray-300"
                                        value="{{ $vendor->id }}">
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-semibold {{ $color }}">
                                            {{ $initials ?: '--' }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-800">{{ $vendor->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $vendor->code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $vendor->category->name ?? '—' }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-gray-700">{{ $vendor->contact_person ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $vendor->email ?? '' }}</div>
                                </td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $vendor->paymentTerm->name ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($vendor->is_active)
                                        <span
                                            class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">Active</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="relative inline-block text-left">
                                        <a href="{{ route('ap.vendors.edit', $vendor->id) }}"
                                            class="text-gray-500 hover:text-blue-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                <path
                                                    d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                                                <path fill-rule="evenodd"
                                                    d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No vendors found.
                                    <a href="{{ route('ap.vendors.create') }}" class="text-blue-600 hover:underline">Add
                                        your first vendor</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>
                    Showing {{ $vendors?->firstItem() ?? 0 }}-{{ $vendors?->lastItem() ?? 0 }} of
                    {{ $vendors?->total() }}
                </div>
                {{ $vendors->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.getElementById('alert-message');
                if (alert) {
                    setTimeout(() => {
                        alert.style.transition = 'opacity 0.5s ease';
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 500);
                    }, 3000);
                }

                const selectAll = document.getElementById('selectAll');
                const rowCheckboxes = document.querySelectorAll('.row-checkbox');
                const selectedCount = document.getElementById('selectedCount');
                const bulkActions = document.getElementById('bulkActions');

                function updateCount() {
                    const checked = document.querySelectorAll('.row-checkbox:checked').length;
                    selectedCount.textContent = `${checked} vendor${checked === 1 ? '' : 's'} selected`;
                    bulkActions.classList.toggle('opacity-50', checked === 0);
                    bulkActions.classList.toggle('pointer-events-none', checked === 0);
                }

                selectAll?.addEventListener('change', function() {
                    rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateCount();
                });

                rowCheckboxes.forEach(cb => cb.addEventListener('change', updateCount));
            });
        </script>
    @endpush
@endsection
