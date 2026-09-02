@extends('dashboard')

@section('title', 'Refunds')

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
                    <h1 class="text-2xl font-bold text-gray-800">Refunds (Employee → Company)</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage refund requests from employees for excess cash advances</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('cm.ca') }}"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        ← Back to Cash Advances
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 px-6 py-4 border-b border-gray-200">
                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                    <div class="text-sm font-medium text-purple-700">Total Refunds</div>
                    <div class="mt-1 text-2xl font-bold text-purple-900">{{ $refunds->total() }}</div>
                </div>
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                    <div class="text-sm font-medium text-yellow-700">Pending Approval</div>
                    <div class="mt-1 text-2xl font-bold text-yellow-900">
                        {{ $refunds->where('approval_status', '1')->count() }}
                    </div>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="text-sm font-medium text-blue-700">Approved</div>
                    <div class="mt-1 text-2xl font-bold text-blue-900">
                        {{ $refunds->where('approval_status', '2')->count() }}
                    </div>
                </div>
                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="text-sm font-medium text-green-700">Paid</div>
                    <div class="mt-1 text-2xl font-bold text-green-900">
                        {{ $refunds->where('status', '5')->count() }}
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('cm.ref') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <div>
                    <select name="searchstatus" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">All Status</option>
                        <option value="0" @selected(request('searchstatus') === '0')>Draft</option>
                        <option value="1" @selected(request('searchstatus') === '1')>Pending Approval</option>
                        <option value="2" @selected(request('searchstatus') === '2')>Approved</option>
                        <option value="3" @selected(request('searchstatus') === '3')>Rejected</option>
                        <option value="4" @selected(request('searchstatus') === '4')>Returned</option>
                        <option value="5" @selected(request('searchstatus') === '5')>Paid</option>
                    </select>
                </div>
                <div>
                    <input type="text" name="searchca" value="{{ request('searchca') }}" placeholder="Search CA #"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs w-40">
                </div>
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    Filter
                </button>
                @if (request()->anyFilled(['searchstatus', 'searchca']))
                    <a href="{{ route('cm.ref') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Refund #</th>
                            <th class="px-3 py-3 text-left">Cash Advance</th>
                            <th class="px-3 py-3 text-left">Employee</th>
                            <th class="px-3 py-3 text-left">Purpose</th>
                            <th class="px-3 py-3 text-right">Amount</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($refunds as $refund)
                            @php
                                $approvalTx = $refund->latestApprovalTransaction;
                                $isPending = $refund->approval_status === '1';
                                $isApproved = $refund->approval_status === '2';
                                $isRejected = $refund->approval_status === '3';
                                $isReturned = $refund->approval_status === '4';
                                $isPaid = $refund->isPaid();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-purple-600">
                                    <button type="button" data-drawer-target="drawer-ref-{{ $refund->id }}"
                                        data-drawer-show="drawer-ref-{{ $refund->id }}" data-drawer-placement="right"
                                        class="text-purple-600 hover:text-purple-800 hover:underline transition-colors">
                                        REF-{{ str_pad($refund->id, 6, '0', STR_PAD_LEFT) }}
                                    </button>
                                </td>
                                <td class="px-3 py-3 text-gray-700">
                                    <a href="{{ route('cm.ca') }}?search={{ $refund->cash_advance_id }}"
                                        class="text-blue-600 hover:underline">
                                        CA-{{ str_pad($refund->cash_advance_id, 6, '0', STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td class="px-3 py-3 text-gray-700">
                                    {{ $refund->employee?->last_name ?? '—' }},
                                    {{ $refund->employee?->first_name ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-gray-700 max-w-xs truncate">
                                    {{ Str::limit($refund->purpose ?? ($refund->cashAdvance?->purpose ?? '—'), 50) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums font-medium text-gray-700">
                                    {{ number_format($refund->amount, 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full {{ $refund->statusBadgeClass() }} px-2 py-0.3 text-[10px] font-medium">
                                        {{ $refund->statusLabel() }}
                                    </span>
                                    @if ($isPending)
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-medium text-yellow-800">
                                            Pending
                                        </span>
                                    @endif
                                    @if ($isPaid)
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-800">
                                            Paid
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <button type="button" data-drawer-target="drawer-ref-{{ $refund->id }}"
                                        data-drawer-show="drawer-ref-{{ $refund->id }}" data-drawer-placement="right"
                                        class="inline-flex items-center gap-1 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-purple-700 transition-colors">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No refunds found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>
                    Showing {{ $refunds->firstItem() ?? 0 }}-{{ $refunds->lastItem() ?? 0 }}
                    of {{ $refunds->total() }}
                </div>
                {{ $refunds->links() }}
            </div>
        </div>
    </div>

    {{-- Drawers --}}
    @foreach ($refunds as $refund)
        @php
            $approvalTx = $refund->latestApprovalTransaction;
            $isPending = $refund->approval_status === '1';
            $isApproved = $refund->approval_status === '2';
            $isRejected = $refund->approval_status === '3';
            $isReturned = $refund->approval_status === '4';
            $isPaid = $refund->isPaid();
        @endphp
        <div id="drawer-ref-{{ $refund->id }}"
            class="fixed top-0 right-0 z-50 h-screen w-[520px] max-w-full overflow-y-auto bg-white p-5 shadow-xl transition-transform translate-x-full"
            tabindex="-1">
            <div class="mb-5 flex items-center border-b border-gray-200 pb-4">
                <div class="flex-1">
                    <h5 class="text-lg font-semibold text-gray-900">
                        REF-{{ str_pad($refund->id, 6, '0', STR_PAD_LEFT) }}
                    </h5>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Refund for CA-{{ str_pad($refund->cash_advance_id, 6, '0', STR_PAD_LEFT) }}
                    </p>
                </div>
                <button type="button" data-drawer-hide="drawer-ref-{{ $refund->id }}"
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
                            <span
                                class="inline-flex rounded-full {{ $refund->statusBadgeClass() }} px-2.5 py-1 text-xs font-medium">
                                {{ $refund->statusLabel() }}
                            </span>
                            @if ($isPending)
                                <span
                                    class="ml-2 inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-medium text-yellow-800">
                                    Pending Approval
                                </span>
                            @endif
                            @if ($isPaid)
                                <span
                                    class="ml-2 inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">
                                    Paid
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Amount</div>
                        <div class="text-lg font-bold text-gray-900">{{ number_format($refund->amount, 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Details</h6>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500">Employee</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $refund->employee?->last_name ?? '—' }}, {{ $refund->employee?->first_name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Cash Advance</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            CA-{{ str_pad($refund->cash_advance_id, 6, '0', STR_PAD_LEFT) }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Amount: {{ number_format($refund->cashAdvance->amount ?? 0, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">GL Account</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $refund->glAccount?->account_code ?? '—' }}
                            {{ $refund->glAccount ? '— ' . $refund->glAccount->account_name : '' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">CA Liquidated</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ number_format($refund->cashAdvance->liquidated_amount ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500">Purpose</label>
                        <p class="mt-1 text-sm text-gray-700">{{ $refund->purpose ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Submitted</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $refund->submitted_at?->format('M d, Y g:i A') ?? '—' }}
                        </p>
                    </div>
                    @if ($isPaid)
                        <div>
                            <label class="block text-xs text-gray-500">Paid Date</label>
                            <p class="mt-1 text-sm font-medium text-gray-900">
                                {{ $refund->paid_at?->format('M d, Y g:i A') ?? '—' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Approval Workflow -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Approval Workflow</h6>

                @if ($approvalTx)
                    <div class="rounded-lg border border-gray-200 p-4">
                        <!-- Workflow Name -->
                        <div class="mb-4">
                            <label class="block text-xs text-gray-500">Workflow</label>
                            <p class="mt-1 text-sm font-medium text-gray-900">
                                {{ $approvalTx->workflow->name ?? '—' }}
                            </p>
                        </div>

                        <!-- Current Step -->
                        @php
                            $currentStep = $approvalTx->currentStep();
                            $stepStatus = $approvalTx->status;
                        @endphp

                        <div class="mb-4">
                            <label class="block text-xs text-gray-500">
                                Status
                                @if ($stepStatus === 'pending')
                                    <span
                                        class="ml-2 inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-medium text-yellow-800">
                                        In Progress
                                    </span>
                                @elseif ($stepStatus === 'approved')
                                    <span
                                        class="ml-2 inline-flex rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-800">
                                        Completed
                                    </span>
                                @elseif ($stepStatus === 'rejected')
                                    <span
                                        class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-800">
                                        Rejected
                                    </span>
                                @elseif ($stepStatus === 'returned')
                                    <span
                                        class="ml-2 inline-flex rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-medium text-orange-800">
                                        Returned
                                    </span>
                                @endif
                            </label>

                            @if ($currentStep && $stepStatus === 'pending')
                                <div class="mt-1 flex items-center gap-2">
                                    <span
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                        {{ $currentStep->step_no }}
                                    </span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $currentStep->step_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Approver: {{ $currentStep->approverLabel() }}
                                        </div>
                                    </div>
                                </div>
                            @elseif ($stepStatus === 'approved')
                                <div class="mt-1 flex items-center gap-2">
                                    <span
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-600">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="text-sm font-medium text-green-700">Approved</div>
                                        <div class="text-xs text-gray-500">All steps completed</div>
                                    </div>
                                </div>
                            @elseif ($stepStatus === 'rejected')
                                <div class="mt-1 flex items-center gap-2">
                                    <span
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-red-600">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="text-sm font-medium text-red-700">Rejected</div>
                                        <div class="text-xs text-gray-500">Request was rejected</div>
                                    </div>
                                </div>
                            @elseif ($stepStatus === 'returned')
                                <div class="mt-1 flex items-center gap-2">
                                    <span
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="text-sm font-medium text-orange-700">Returned to Requester</div>
                                        <div class="text-xs text-gray-500">Request needs revision</div>
                                    </div>
                                </div>
                            @else
                                <p class="mt-1 text-sm text-gray-500">No active step</p>
                            @endif
                        </div>

                        <!-- Workflow Steps -->
                        @if ($approvalTx->workflow?->steps?->isNotEmpty())
                            <div class="border-t border-gray-100 pt-3">
                                <div class="mb-2 text-xs font-medium text-gray-600">Workflow Steps</div>
                                <div class="space-y-2">
                                    @foreach ($approvalTx->workflow->steps as $step)
                                        @php
                                            $isCurrent =
                                                $stepStatus === 'pending' &&
                                                $approvalTx->current_step_no == $step->step_no;
                                            $isPast =
                                                $stepStatus === 'approved' ||
                                                ($stepStatus === 'pending' &&
                                                    $approvalTx->current_step_no > $step->step_no) ||
                                                ($stepStatus === 'pending' &&
                                                    $approvalTx->current_step_no == $step->step_no &&
                                                    $step->is_final_approval);
                                            $isRejected = $stepStatus === 'rejected';
                                            $isReturned = $stepStatus === 'returned';
                                        @endphp

                                        <div class="flex items-center gap-2">
                                            @if ($isPast && !$isRejected && !$isReturned)
                                                <span
                                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-600">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </span>
                                            @elseif ($isCurrent && !$isRejected && !$isReturned)
                                                <span
                                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                                    {{ $step->step_no }}
                                                </span>
                                            @elseif ($isRejected)
                                                <span
                                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-red-600">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </span>
                                            @else
                                                <span
                                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs text-gray-500">
                                                    {{ $step->step_no }}
                                                </span>
                                            @endif

                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-medium text-gray-800">
                                                    {{ $step->step_name }}
                                                    @if ($step->is_final_approval && $isCurrent)
                                                        <span
                                                            class="ml-1 text-[10px] font-medium text-blue-600">(Final)</span>
                                                    @endif
                                                </div>
                                                <div class="text-[10px] text-gray-500">
                                                    {{ $step->approverLabel() }}
                                                </div>
                                            </div>

                                            @if ($isCurrent && !$isRejected && !$isReturned)
                                                <span
                                                    class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700">Current</span>
                                            @endif
                                            @if ($isPast && !$isRejected && !$isReturned && !$isCurrent)
                                                <span
                                                    class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-700">Done</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 p-4 text-center">
                        <p class="text-xs text-gray-500">This refund has not been submitted for approval.</p>
                    </div>
                @endif
            </div>

            <!-- Approval History -->
            @if ($approvalTx && $approvalTx->histories->isNotEmpty())
                <div class="mb-6">
                    <h6 class="mb-3 text-sm font-semibold text-gray-900">Approval History</h6>
                    <div class="rounded-lg border border-gray-200 p-4 max-h-48 overflow-y-auto">
                        @foreach ($approvalTx->histories->sortByDesc('acted_at') as $history)
                            <div class="relative flex gap-3 py-2 border-b border-gray-100 last:border-b-0">
                                <div class="flex flex-col items-center">
                                    <div
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full
                                    {{ $history->action === 'approved'
                                        ? 'bg-green-100 text-green-600'
                                        : ($history->action === 'rejected'
                                            ? 'bg-red-100 text-red-600'
                                            : ($history->action === 'returned'
                                                ? 'bg-orange-100 text-orange-600'
                                                : 'bg-blue-100 text-blue-600')) }}">
                                        @if ($history->action === 'approved')
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        @elseif ($history->action === 'rejected')
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        @elseif ($history->action === 'returned')
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                            </svg>
                                        @else
                                            <span class="text-[10px] font-bold">S</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <div class="text-xs font-semibold capitalize text-gray-800">
                                                {{ $history->action }}
                                                @if ($history->step_no > 0)
                                                    <span class="font-normal text-gray-400">(Step
                                                        {{ $history->step_no }})</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-gray-500">
                                                {{ $history->actor ? trim($history->actor->last_name . ', ' . $history->actor->first_name . ' ' . ($history->actor->middle_name ?? '')) : 'System' }}
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-[10px] text-gray-400">
                                            {{ $history->acted_at->format('M d, Y g:i A') }}
                                        </div>
                                    </div>

                                    @if ($history->remarks)
                                        <div class="mt-1 rounded-md bg-gray-50 p-2 text-xs text-gray-600">
                                            {{ $history->remarks }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (in_array($refund->approval_status, ['0', '4']) && $refund->employee_id == Auth::id())
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <a href="{{ route('cm.refund.edit', $refund) }}"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-purple-700">
                        Edit Refund
                    </a>
                </div>
            @endif

            @if ($isPending)
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <div class="rounded-lg bg-yellow-50 p-3 text-center text-xs text-yellow-800">
                        <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.334-.213 2.98-1.742 2.98H3.48c-1.53 0-2.492-1.646-1.743-2.98l6.518-11.59zM10 13a1 1 0 100-2 1 1 0 000 2zm-1-8a1 1 0 011 1v3a1 1 0 11-2 0V6a1 1 0 011-1z"
                                clip-rule="evenodd" />
                        </svg>
                        This refund is currently pending approval.
                    </div>
                </div>
            @endif
        </div>
    @endforeach
@endsection
