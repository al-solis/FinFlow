@extends('dashboard')

@section('title', 'Customer Master')

@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Customer Master</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage customer records used across the Accounts Receivable
                        module.</p>
                    <div class="mt-2">{!! $maskBadge !!}</div>
                </div>
                @if ($canCreate)
                    <a href="{{ route('ar.customers.create') }}"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        + Add Customer
                    </a>
                @endif
            </div>

            <form method="GET" action="{{ route('ar.cust') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <input type="text" name="searchname" value="{{ request('searchname') }}"
                    placeholder="Search by code or name"
                    class="flex-1 min-w-[220px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                <select name="searchcategory" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('searchcategory') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="searchstatus" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                    <option value="">All Status</option>
                    <option value="1" @selected(request('searchstatus') === '1')>Active</option>
                    <option value="0" @selected(request('searchstatus') === '0')>Inactive</option>
                </select>
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    Filter
                </button>
                @if (request()->anyFilled(['searchname', 'searchcategory', 'searchstatus']))
                    <a href="{{ route('ar.cust') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Customer</th>
                            <th class="px-3 py-3 text-left">Category</th>
                            <th class="px-3 py-3 text-left">TIN</th>
                            <th class="px-3 py-3 text-left">Terms</th>
                            <th class="px-3 py-3 text-right">Credit Limit</th>
                            <th class="px-3 py-3 text-right">Open Balance</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            @if ($canUpdate)
                                <th class="px-3 py-3 text-right w-16">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($customers as $customer)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <div class="font-medium text-gray-800">{{ $customer->name }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $customer->code }}</div>
                                </td>
                                <td class="px-3 py-3 text-gray-600">{{ $customer->category->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-gray-600">{{ $customer->tax_id ?: '—' }}</td>
                                <td class="px-3 py-3 text-gray-600">{{ $customer->paymentTerm->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-700">
                                    {{ $customer->credit_limit > 0 ? number_format($customer->credit_limit, 2) : 'No limit' }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums font-medium text-gray-800">
                                    {{ number_format((float) $customer->open_balance, 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($customer->credit_hold)
                                        <span class="rounded-full bg-orange-50 px-2.5 py-1 text-orange-700">On Hold</span>
                                    @elseif ($customer->is_active)
                                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Active</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-500">Inactive</span>
                                    @endif
                                </td>
                                @if ($canUpdate)
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('ar.customers.edit', $customer) }}" title="Edit Customer"
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
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canUpdate ? 8 : 7 }}" class="px-6 py-12 text-center text-gray-500">
                                    No customers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $customers->firstItem() ?? 0 }}-{{ $customers->lastItem() ?? 0 }} of
                    {{ $customers->total() }}</div>
                {{ $customers->links() }}
            </div>
        </div>
    </div>
@endsection
