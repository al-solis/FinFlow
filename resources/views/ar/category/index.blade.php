@extends('dashboard')

@section('title', 'Customer Categories')

@section('content')
    <div class="mx-auto max-w-6xl">
        @if (session('success'))
            <div class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between border-b px-6 py-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Customer Categories</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage customer categories used across the Accounts Receivable
                        module.</p>
                </div>
                @if ($canCreate)
                    <a href="{{ route('ar.categories.create') }}"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        + Add Category
                    </a>
                @endif
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-1 gap-4 border-b px-6 py-4 sm:grid-cols-3">
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="text-xs font-medium text-blue-700">Total Categories</div>
                    <div class="mt-1 text-xl font-bold text-blue-900">{{ $totalCount }}</div>
                </div>
                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="text-xs font-medium text-green-700">Active</div>
                    <div class="mt-1 text-xl font-bold text-green-900">{{ $activeCount }}</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <div class="text-xs font-medium text-red-700">Inactive</div>
                    <div class="mt-1 text-xl font-bold text-red-900">{{ $inactiveCount }}</div>
                </div>
            </div>

            <form method="GET" action="{{ route('ar.catg') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <input type="text" name="searchname" value="{{ request('searchname') }}"
                    placeholder="Search by code, name, or description"
                    class="flex-1 min-w-[220px] rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
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
                @if (request()->anyFilled(['searchname', 'searchstatus']))
                    <a href="{{ route('ar.catg') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Code</th>
                            <th class="px-3 py-3 text-left">Name</th>
                            <th class="px-3 py-3 text-left">Description</th>
                            <th class="px-3 py-3 text-right">Customers</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            @if ($canUpdate || $canDelete)
                                <th class="px-3 py-3 text-right w-24">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($categories as $category)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-800">{{ $category->code }}</td>
                                <td class="px-3 py-3 text-gray-700">{{ $category->name }}</td>
                                <td class="px-3 py-3 text-gray-500">{{ $category->description ?: '—' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-700">
                                    {{ $category->customers_count }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if ($category->status)
                                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Active</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-500">Inactive</span>
                                    @endif
                                </td>
                                @if ($canUpdate || $canDelete)
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if ($canUpdate)
                                                <a href="{{ route('ar.categories.edit', $category) }}"
                                                    title="Edit Category"
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
                                                <form method="POST" action="{{ route('ar.categories.destroy', $category) }}"
                                                    onsubmit="return confirm('Delete category {{ $category->code }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Delete Category"
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
                                <td colspan="{{ $canUpdate || $canDelete ? 6 : 5 }}"
                                    class="px-6 py-12 text-center text-gray-500">No categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>Showing {{ $categories->firstItem() ?? 0 }}-{{ $categories->lastItem() ?? 0 }} of
                    {{ $categories->total() }}</div>
                {{ $categories->links() }}
            </div>
        </div>
    </div>
@endsection
