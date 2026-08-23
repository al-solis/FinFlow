@extends('dashboard')

@section('title', 'Approval Workflows')

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
                    <h1 class="text-2xl font-bold text-gray-800">Approval Workflows</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage approval workflows across the organization.
                    </p>
                </div>

                <a href="{{ route('appw.create') }}"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    + Add Workflow
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('appw.appr') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <div class="relative flex-1 min-w-[240px]">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by code, name, or branch"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-xs">
                </div>

                <div>
                    <select name="module_code" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-white p-2 text-xs text-gray-900 focus:border-[#2D3452] focus:ring-[#2D3452] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">All modules</option>
                        @foreach ($moduleOptions as $code => $label)
                            <option value="{{ $code }}" @selected(request('module_code') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="searchstatus" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">All Status</option>
                        <option value="1" @selected(request('searchstatus') === '1')>Active</option>
                        <option value="0" @selected(request('searchstatus') === '0')>Inactive</option>
                    </select>
                </div>

                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    Filter
                </button>

                @if (request()->anyFilled(['search', 'searchstatus']))
                    <a href="{{ route('bm.bank') }}" class="text-xs text-gray-500 hover:text-gray-700">
                        Clear
                    </a>
                @endif
            </form>

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
                                    'label' => 'Module Name',
                                ])
                            </th>
                            <th class="px-3 py-3 text-left">Workflow</th>
                            <th class="px-3 py-3 text-left">Amount Range</th>
                            <th class="px-3 py-3 text-left">Steps</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right w-16">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($bankAccounts as $bankAccount)
                            @php
                                $colors = [
                                    'bg-red-100 text-red-700',
                                    'bg-blue-100 text-blue-700',
                                    'bg-green-100 text-green-700',
                                    'bg-yellow-100 text-yellow-700',
                                    'bg-purple-100 text-purple-700',
                                    'bg-pink-100 text-pink-700',
                                ];
                                $initials = collect(explode(' ', $bankAccount->name))
                                    ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                                    ->take(2)
                                    ->implode('');
                                $color = $colors[$bankAccount->id % count($colors)];
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <input type="checkbox" class="row-checkbox rounded border-gray-300"
                                        value="{{ $bankAccount->id }}">
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-semibold {{ $color }}">
                                            {{ $initials ?: '--' }}
                                        </div>
                                        <div>
                                            <!-- Bank Name with Drawer Trigger -->
                                            <div class="font-medium text-gray-800 hover:text-blue-600 cursor-pointer transition-colors duration-200"
                                                data-drawer-target="drawer-bank-{{ $bankAccount->id }}"
                                                data-drawer-show="drawer-bank-{{ $bankAccount->id }}"
                                                data-drawer-placement="right"
                                                aria-controls="drawer-bank-{{ $bankAccount->id }}">
                                                {{ $bankAccount->name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                @php
                                                    $types = [
                                                        '1' => 'Savings',
                                                        '2' => 'Current',
                                                        '3' => 'Time Deposit',
                                                        '4' => 'Money Market',
                                                        '5' => 'Others',
                                                    ];
                                                @endphp
                                                {{ $types[$bankAccount->account_type] ?? '—' }} -
                                                {{ $bankAccount->account_number }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $bankAccount->account_name ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $bankAccount->branch ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $bankAccount->currency->code ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-gray-600">
                                    {{ $bankAccount->chartOfAccount?->getFormattedAccountCodeAttribute() ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($bankAccount->status == 1)
                                        <span
                                            class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">Active</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="relative inline-block text-left">
                                        <a href="{{ route('bm.bank.edit', $bankAccount->id) }}"
                                            title="Edit Bank Account : {{ $bankAccount->name }}"
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

                            <!-- Drawer Component for each bank -->
                            {{-- transparent - bg-neutral-primary-soft --}}
                            <div id="drawer-bank-{{ $bankAccount->id }}"
                                class="fixed top-0 right-0 z-40 h-screen p-4 overflow-y-auto transition-transform translate-x-full bg-white w-96"
                                tabindex="-1" aria-labelledby="drawer-bank-label-{{ $bankAccount->id }}">

                                <div class="border-b border-gray-200 pb-4 mb-5 flex items-center">
                                    <h5 id="drawer-bank-label-{{ $bankAccount->id }}"
                                        class="inline-flex items-center text-lg font-medium text-body">
                                        <svg class="w-5 h-5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                            width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 10h18M5 6h14a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z" />
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="M8 14h1m4 0h1" />
                                        </svg>
                                        {{ $bankAccount->name }}
                                    </h5>
                                    <button type="button" data-drawer-hide="drawer-bank-{{ $bankAccount->id }}"
                                        aria-controls="drawer-bank-{{ $bankAccount->id }}"
                                        class="text-gray-500 bg-transparent hover:text-gray-900 hover:bg-gray-100 rounded-base w-9 h-9 absolute top-2.5 end-2.5 flex items-center justify-center">
                                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                            width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
                                        </svg>
                                        <span class="sr-only">Close menu</span>
                                    </button>
                                </div>

                                <!-- Drawer Content -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Bank Name</label>
                                        <p class="text-sm font-medium text-gray-900">{{ $bankAccount->name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Account Number</label>
                                        <p class="text-sm font-medium text-gray-900">{{ $bankAccount->account_number }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Account Name</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $bankAccount->account_name ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Account Type</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $types[$bankAccount->account_type] ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Branch</label>
                                        <p class="text-sm font-medium text-gray-900">{{ $bankAccount->branch ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Currency</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $bankAccount->currency->code ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">GL Account</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $bankAccount->chartOfAccount->account_code ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Status</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            @if ($bankAccount->status == 1)
                                                <span class="text-green-600">Active</span>
                                            @else
                                                <span class="text-gray-600">Inactive</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Created At</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $bankAccount->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Last Updated</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $bankAccount->updated_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 mt-6 pt-4 border-t border-gray-200">
                                    <a href="{{ route('bm.bank.edit', $bankAccount->id) }}"
                                        class="inline-flex items-center justify-center text-white bg-blue-600 box-border border border-transparent hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none w-full">
                                        Edit Bank Account
                                        <svg class="rtl:rotate-180 w-4 h-4 ms-1.5 -me-0.5" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <img src="{{ asset('images/bank.svg') }}" alt="No data"
                                        class="mx-auto mb-4 w-24 h-28">
                                    No bank accounts found.
                                    <a href="{{ route('bm.bank.create') }}" class="text-blue-600 hover:underline">Add
                                        your first bank account</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">


                @if ($workflows->isEmpty())
                    <div class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        No approval workflows match these filters yet.<br>
                        <a href="{{ route('appw.create') }}"
                            class="font-semibold text-[#2D3452] hover:underline dark:text-white">
                            Create the first one
                        </a>.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead
                                class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Module</th>
                                    <th class="px-4 py-3 font-semibold">Workflow</th>
                                    <th class="px-4 py-3 font-semibold">Amount range</th>
                                    <th class="px-4 py-3 font-semibold">Steps</th>
                                    <th class="px-4 py-3 font-semibold">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($workflows as $workflow)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-md bg-[#EEF1F8] px-2.5 py-1 text-xs font-semibold text-[#2D3452] dark:bg-gray-700 dark:text-gray-200">
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-[#2D3452] dark:bg-gray-300"></span>
                                                {{ $workflow->moduleLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ $workflow->name }}
                                            </div>
                                            @if ($workflow->description)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ Str::limit($workflow->description, 70) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 tabular-nums text-gray-500 dark:text-gray-400">
                                            @if ($workflow->min_amount || $workflow->max_amount)
                                                {{ $workflow->min_amount ? number_format($workflow->min_amount, 2) : '0.00' }}
                                                &ndash;
                                                {{ $workflow->max_amount ? number_format($workflow->max_amount, 2) : '∞' }}
                                            @else
                                                Any amount
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 tabular-nums text-gray-500 dark:text-gray-400">
                                            {{ $workflow->steps_count }} step{{ $workflow->steps_count === 1 ? '' : 's' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($workflow->is_active)
                                                <span
                                                    class="rounded-full bg-[#E7F5F2] px-2.5 py-1 text-xs font-semibold text-[#0F9B8E] dark:bg-gray-700 dark:text-[#3ecbb9]">
                                                    Active
                                                </span>
                                            @else
                                                <span
                                                    class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('approval-workflows.edit', $workflow) }}"
                                                class="text-sm font-semibold text-[#2D3452] hover:underline dark:text-white">Edit</a>
                                            <form action="{{ route('approval-workflows.destroy', $workflow) }}"
                                                method="POST" class="inline"
                                                onsubmit="return confirm('Delete this workflow? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="ms-4 border-0 bg-transparent text-sm font-semibold text-red-600 hover:underline dark:text-red-400">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="mt-4">
                {{ $workflows->links() }}
            </div>
        </div>
    </div>


@endsection
