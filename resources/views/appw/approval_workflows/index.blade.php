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
                        class="text-red-600 hover:text-red-800 transition-colors duration-200">
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
                    <p class="mt-1 text-sm text-gray-500">Define approval processes for financial transactions across
                        modules.</p>
                </div>

                <a href="{{ route('appw.create') }}"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    + New Workflow
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('appw.appr') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="searchname" value="{{ request('searchname') }}"
                        placeholder="Search by workflow name"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-xs">
                </div>

                <div>
                    <select name="searchmodule" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">All Modules</option>
                        @foreach ($moduleOptions as $code => $label)
                            <option value="{{ $code }}" @selected(request('searchmodule') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="searchstatus" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('searchstatus') === 'active')>Active</option>
                        <option value="inactive" @selected(request('searchstatus') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    Filter
                </button>

                @if (request()->anyFilled(['searchname', 'searchmodule', 'searchstatus']))
                    <a href="{{ route('appw.index') }}" class="text-xs text-gray-500 hover:text-gray-700">
                        Clear
                    </a>
                @endif
            </form>

            <!-- Bulk action bar -->
            <div class="flex items-center justify-between px-6 py-3 text-xs text-gray-500" id="bulkBar">
                <span id="selectedCount">0 workflows selected</span>
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
                            <th class="px-3 py-3 text-left">Module</th>
                            <th class="px-3 py-3 text-left">Workflow</th>
                            <th class="px-3 py-3 text-left">Amount Range</th>
                            <th class="px-3 py-3 text-left">Steps</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right w-16">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($workflows as $workflow)
                            @php
                                $colors = [
                                    'bg-red-100 text-red-700',
                                    'bg-blue-100 text-blue-700',
                                    'bg-green-100 text-green-700',
                                    'bg-yellow-100 text-yellow-700',
                                    'bg-purple-100 text-purple-700',
                                    'bg-pink-100 text-pink-700',
                                ];
                                $initials = collect(explode(' ', $workflow->name))
                                    ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                                    ->take(2)
                                    ->implode('');
                                $color = $colors[$workflow->id % count($colors)];
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <input type="checkbox" class="row-checkbox rounded border-gray-300"
                                        value="{{ $workflow->id }}">
                                </td>
                                <td class="px-3 py-3">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-600"></span>
                                        {{ $workflow->moduleLabel() }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-semibold {{ $color }}">
                                            {{ $initials ?: 'WF' }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-blue-600 hover:text-blue-800 hover:underline cursor-pointer transition-colors duration-200"
                                                data-drawer-target="drawer-workflow-{{ $workflow->id }}"
                                                data-drawer-show="drawer-workflow-{{ $workflow->id }}"
                                                data-drawer-placement="right"
                                                aria-controls="drawer-workflow-{{ $workflow->id }}">
                                                {{ $workflow->name }}
                                            </div>
                                            @if ($workflow->description)
                                                <div class="text-xs text-gray-500">
                                                    {{ Str::limit($workflow->description, 60) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 tabular-nums text-gray-500">
                                    @if ($workflow->min_amount || $workflow->max_amount)
                                        {{ $workflow->min_amount ? number_format($workflow->min_amount, 2) : '0.00' }}
                                        &ndash;
                                        {{ $workflow->max_amount ? number_format($workflow->max_amount, 2) : '∞' }}
                                    @else
                                        <span class="text-gray-400">Any amount</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 tabular-nums text-gray-500">
                                    {{ $workflow->steps_count }} step{{ $workflow->steps_count === 1 ? '' : 's' }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($workflow->is_active)
                                        <span
                                            class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">Active</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="relative inline-block text-left">
                                        <a href="{{ route('appw.edit', $workflow) }}"
                                            title="Edit Workflow : {{ $workflow->name }}"
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

                            <!-- Drawer Component for each workflow -->
                            <div id="drawer-workflow-{{ $workflow->id }}"
                                class="fixed top-0 right-0 z-40 h-screen p-4 overflow-y-auto transition-transform translate-x-full bg-white w-96"
                                tabindex="-1" aria-labelledby="drawer-workflow-label-{{ $workflow->id }}">

                                <div class="border-b border-gray-200 pb-4 mb-5 flex items-center">
                                    <h5 id="drawer-workflow-label-{{ $workflow->id }}"
                                        class="inline-flex items-center text-lg font-medium text-body">
                                        <svg class="w-5 h-5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                            width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5Z M3 12h18M4 15h16M5 18h14" />
                                        </svg>
                                        {{ $workflow->name }}
                                    </h5>
                                    <button type="button" data-drawer-hide="drawer-workflow-{{ $workflow->id }}"
                                        aria-controls="drawer-workflow-{{ $workflow->id }}"
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
                                        <label class="block text-xs font-medium text-gray-500">Module</label>
                                        <p class="text-sm font-medium text-gray-900">{{ $workflow->moduleLabel() }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Workflow Name</label>
                                        <p class="text-sm font-medium text-gray-900">{{ $workflow->name }}</p>
                                    </div>
                                    @if ($workflow->description)
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500">Description</label>
                                            <p class="text-sm font-medium text-gray-900">{{ $workflow->description }}</p>
                                        </div>
                                    @endif
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Amount Range</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            @if ($workflow->min_amount || $workflow->max_amount)
                                                {{ $workflow->min_amount ? number_format($workflow->min_amount, 2) : '0.00' }}
                                                &ndash;
                                                {{ $workflow->max_amount ? number_format($workflow->max_amount, 2) : '∞' }}
                                            @else
                                                <span class="text-gray-400">Any amount</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Approval Steps</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $workflow->steps_count }} step{{ $workflow->steps_count === 1 ? '' : 's' }}
                                        </p>
                                        @if ($workflow->steps_count > 0)
                                            <div class="mt-2 space-y-1.5">
                                                @foreach ($workflow->steps as $step)
                                                    <div class="flex items-center gap-2 text-xs text-gray-600">
                                                        <span
                                                            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-700">
                                                            {{ $step->step_no }}
                                                        </span>
                                                        <span>{{ $step->step_name }}</span>
                                                        <span class="text-gray-400">·</span>
                                                        <span class="text-gray-500">{{ $step->approverLabel() }}</span>
                                                        @if ($step->is_final_approval)
                                                            <span
                                                                class="ml-auto rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-600">Final</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Status</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            @if ($workflow->is_active)
                                                <span class="text-green-600">Active</span>
                                            @else
                                                <span class="text-gray-600">Inactive</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Created At</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $workflow->created_at->format('M d, Y H:i') }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Last Updated</label>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $workflow->updated_at->format('M d, Y H:i') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 mt-6 pt-4 border-t border-gray-200">
                                    <a href="{{ route('appw.edit', $workflow) }}"
                                        class="inline-flex items-center justify-center text-white bg-blue-600 box-border border border-transparent hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none w-full">
                                        Edit Workflow
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
                                    <svg class="mx-auto mb-4 w-16 h-16 text-gray-300" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    No approval workflows found.
                                    <a href="{{ route('appw.create') }}" class="text-blue-600 hover:underline">Create
                                        your first workflow</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>
                    Showing {{ $workflows->firstItem() ?? 0 }}-{{ $workflows->lastItem() ?? 0 }} of
                    {{ $workflows->total() }}
                </div>
                {{ $workflows->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectAll = document.getElementById('selectAll');
                const rowCheckboxes = document.querySelectorAll('.row-checkbox');
                const selectedCount = document.getElementById('selectedCount');
                const bulkActions = document.getElementById('bulkActions');

                function updateCount() {
                    const checked = document.querySelectorAll('.row-checkbox:checked').length;
                    selectedCount.textContent = `${checked} workflow${checked === 1 ? '' : 's'} selected`;
                    bulkActions.classList.toggle('opacity-50', checked === 0);
                    bulkActions.classList.toggle('pointer-events-none', checked === 0);
                }

                selectAll?.addEventListener('change', function() {
                    rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateCount();
                });

                rowCheckboxes.forEach(cb => cb.addEventListener('change', updateCount));

                // Auto-close alert after 5 seconds
                document.querySelectorAll('[id$="-alert"]').forEach(alert => {
                    setTimeout(() => {
                        alert.style.transition = 'opacity 0.5s ease';
                        alert.style.opacity = '0';
                        setTimeout(() => {
                            alert.style.display = 'none';
                        }, 500);
                    }, 5000);
                });
            });
        </script>
    @endpush
@endsection
