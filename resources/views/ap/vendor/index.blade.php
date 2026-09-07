{{-- resources/views/ap/vendor/index.blade.php --}}
@extends('dashboard')

@section('title', 'Vendor Master')

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
            <!-- Header -->
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Vendor Master</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage supplier records used across the Accounts Payable module.
                    </p>

                    <!-- PII Access Badge -->
                    <div class="mt-2">
                        @piiAccessBadge
                        <span class="ml-2 text-xs text-gray-500">
                            {{ getPiiMaskLevel() === 'none' ? 'Full access to personal data' : 'Limited access - PII masked' }}
                        </span>
                    </div>
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
                    <a href="{{ route('ap.vendors') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
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
                            <th class="px-3 py-3 text-left">Vendor</th>
                            <th class="px-3 py-3 text-left">Category</th>
                            <th class="px-3 py-3 text-left">Contact</th>
                            <th class="px-3 py-3 text-left">Payment Term</th>
                            <th class="px-3 py-3 text-left">AP Control Account</th>
                            <th class="px-3 py-3 text-left">Balance</th>
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
                                    <button type="button" data-drawer-target="drawer-vendor-{{ $vendor->id }}"
                                        data-drawer-show="drawer-vendor-{{ $vendor->id }}"
                                        data-drawer-placement="right"
                                        class="flex items-center gap-3 w-full text-left hover:text-blue-600 transition-colors">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-semibold {{ $color }}">
                                            {{ $initials ?: '--' }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-800">
                                                {{ maskPii($vendor->name, 'full_name') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ maskPii($vendor->code, 'employee_id') }}
                                            </div>
                                        </div>
                                    </button>
                                </td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $vendor->category->name ?? '—' }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-gray-700">
                                        {{ maskPii($vendor->contact_person, 'full_name') }}
                                    </div>
            </div>
            <div class="text-xs text-gray-400">
                {{ maskPii($vendor->email, 'email') ?: '—' }}
            </div>
            </td>
            <td class="px-3 py-3 text-gray-600">
                {{ $vendor->paymentTerm->name ?? '—' }}
            </td>
            <td class="px-3 py-3 text-gray-600">
                {{ $vendor->defaultApChartOfAccount?->getFormattedAccountCodeAttribute() ?? '—' }}
            </td>
            <td class="px-3 py-3 text-right text-gray-600">
                {{ number_format($vendor->getInvoiceBalanceAttribute() ?? 0, 2) }}
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
                <div class="flex items-center justify-end gap-1">
                    <a href="{{ route('ap.vendors.edit', $vendor->id) }}" title="Edit"
                        class="text-gray-500 hover:text-blue-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path
                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                            <path fill-rule="evenodd"
                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                        </svg>
                    </a>
                    <button type="button" data-drawer-target="drawer-vendor-{{ $vendor->id }}"
                        data-drawer-show="drawer-vendor-{{ $vendor->id }}" data-drawer-placement="right"
                        class="text-gray-500 hover:text-blue-600 transition-colors" title="View Details">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            viewBox="0 0 16 16">
                            <path
                                d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z" />
                            <path
                                d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z" />
                        </svg>
                    </button>
                </div>
            </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
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
                Showing {{ $vendors->firstItem() ?? 0 }}-{{ $vendors->lastItem() ?? 0 }}
                of {{ $vendors->total() }}
            </div>
            {{ $vendors->links() }}
        </div>
    </div>
    </div>

    {{-- Vendor Drawers --}}
    @foreach ($vendors as $vendor)
        <div id="drawer-vendor-{{ $vendor->id }}"
            class="fixed top-0 right-0 z-50 h-screen w-[520px] max-w-full overflow-y-auto bg-white p-5 shadow-xl transition-transform translate-x-full"
            tabindex="-1" aria-labelledby="drawer-label-{{ $vendor->id }}" role="dialog">

            <div class="mb-5 flex items-center border-b border-gray-200 pb-4">
                <div class="flex-1">
                    <h5 id="drawer-label-{{ $vendor->id }}" class="text-lg font-semibold text-gray-900">
                        {{ maskPii($vendor->name, 'full_name') }}
                    </h5>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ maskPii($vendor->code, 'employee_id') }} · {{ $vendor->category->name ?? 'Uncategorized' }}
                    </p>
                </div>
                <button type="button" data-drawer-hide="drawer-vendor-{{ $vendor->id }}"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Status Summary -->
            <div class="mb-5 rounded-lg border p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-medium text-gray-500">Status</div>
                        <div class="mt-1">
                            @if ($vendor->is_active)
                                <span
                                    class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">Active</span>
                            @else
                                <span
                                    class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">Inactive</span>
                            @endif
                            @if ($vendor->preferred_vendor)
                                <span
                                    class="ml-2 inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-medium text-yellow-800">Preferred</span>
                            @endif
                            @if ($vendor->is_blacklisted)
                                <span
                                    class="ml-2 inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800">Blacklisted</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Balance</div>
                        <div class="text-lg font-bold text-gray-900">
                            {{ number_format($vendor->getInvoiceBalanceAttribute() ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Vendor Details</h6>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500">Code</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ maskPii($vendor->code, 'employee_id') ?: '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Category</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->category->name ?? '—' }}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500">Legal Name</label>
                        <p class="mt-1 text-sm text-gray-700">{{ maskPii($vendor->legal_name, 'full_name') ?: '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">TIN</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ maskPii($vendor->tax_id, 'tax_id') ?: '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Industry</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->industry ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Contact Information</h6>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500">Contact Person</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ maskPii($vendor->contact_person, 'full_name') ?: '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Position</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->position ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Email</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ maskPii($vendor->email, 'email') ?: '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Phone</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ maskPii($vendor->phone, 'phone') ?: '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Mobile</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ maskPii($vendor->mobile, 'phone') ?: '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Website</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->website ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Address</h6>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500">Address</label>
                        <p class="mt-1 text-sm text-gray-700">
                            {{ maskPii($vendor->address1, 'address') ?? '' }}
                            @if ($vendor->address2)
                                <br>{{ maskPii($vendor->address2, 'address') }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">City</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->city ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Province</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->province ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Country</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->country ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">ZIP Code</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->zip_code ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Financial Information</h6>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500">Currency</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->currency->code ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Payment Term</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->paymentTerm->name ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Payment Method</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $vendor->paymentMethod->name ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">AP Control Account</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $vendor->defaultApChartOfAccount?->getFormattedAccountCodeAttribute() ?? '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $vendor->defaultApChartOfAccount?->account_name ?? '' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Total Invoices</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ number_format($vendor->getInvoiceTotalAmountAttribute() ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Invoices Balance</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ number_format($vendor->getInvoiceBalanceAttribute() ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Bank Accounts -->
            @if ($vendor->bankAccounts->isNotEmpty())
                <div class="mb-6">
                    <h6 class="mb-3 text-sm font-semibold text-gray-900">Bank Accounts</h6>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Bank</th>
                                    <th class="px-3 py-2 text-left">Branch</th>
                                    <th class="px-3 py-2 text-left">Account Name</th>
                                    <th class="px-3 py-2 text-left">Account Number</th>
                                    <th class="px-3 py-2 text-center">Primary</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($vendor->bankAccounts as $bank)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700">{{ $bank->bank_name }}</td>
                                        <td class="px-3 py-2 text-gray-500">{{ $bank->branch ?? '—' }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $bank->account_name ?? '—' }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $bank->account_number ?? '—' }}</td>
                                        <td class="px-3 py-2 text-center">
                                            @if ($bank->is_primary)
                                                <span
                                                    class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Primary</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Attachments -->
            @if ($vendor->attachments->isNotEmpty())
                <div class="mb-6">
                    <h6 class="mb-3 text-sm font-semibold text-gray-900">Attachments</h6>
                    <div class="space-y-2">
                        @foreach ($vendor->attachments as $attachment)
                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        @php
                                            $ext = pathinfo($attachment->original_name, PATHINFO_EXTENSION);
                                            $icon = match (strtolower($ext)) {
                                                'pdf' => 'text-red-500',
                                                'doc', 'docx' => 'text-blue-500',
                                                'xls', 'xlsx' => 'text-green-500',
                                                'jpg', 'jpeg', 'png' => 'text-purple-500',
                                                'zip' => 'text-yellow-500',
                                                default => 'text-gray-400',
                                            };
                                        @endphp
                                        <svg class="h-6 w-6 {{ $icon }}" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">{{ $attachment->original_name }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ number_format($attachment->file_size / 1024, 1) }} KB</p>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                    View
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Audit Information -->
            <div class="mb-6 border-t border-gray-200 pt-4">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Audit Information</h6>
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block text-gray-500">Created By</label>
                        <p class="font-medium text-gray-900">
                            {{ optional($vendor->createdBy)->last_name . ', ' . optional($vendor->createdBy)->first_name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-gray-500">Created At</label>
                        <p class="font-medium text-gray-900">{{ $vendor->created_at->format('M d, Y g:i A') }}</p>
                    </div>
                    <div>
                        <label class="block text-gray-500">Updated By</label>
                        <p class="font-medium text-gray-900">
                            {{ optional($vendor->updatedBy)->last_name . ', ' . optional($vendor->updatedBy)->first_name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-gray-500">Updated At</label>
                        <p class="font-medium text-gray-900">{{ $vendor->updated_at->format('M d, Y g:i A') }}</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 border-t border-gray-200 pt-4 space-y-2">
                <a href="{{ route('ap.vendors.edit', $vendor->id) }}"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    Edit Vendor
                    <svg class="ms-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 12H5m14 0-4 4m4-4-4-4" />
                    </svg>
                </a>
            </div>
        </div>
    @endforeach

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

                // Drawer initialization - Flowbite will handle this automatically
                // The data-drawer attributes will work with Flowbite's drawer plugin
            });
        </script>
    @endpush
@endsection
