@extends('dashboard')

@section('title', 'Cash Advance Liquidations')

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
                    <h1 class="text-2xl font-bold text-gray-800">Cash Advance Liquidations</h1>
                    <p class="mt-1 text-sm text-gray-500">View and track cash advance liquidation requests</p>
                </div>
                <div>
                    <a href="{{ route('cm.ca') }}"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        ← Back to Cash Advances
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('cm.liq') }}"
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
                    </select>
                </div>
                <div>
                    <input type="text" name="searchca" value="{{ request('searchca') }}" placeholder="Search CA #"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs w-40">
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
                @if (request()->anyFilled(['searchstatus', 'searchca', 'datefrom', 'dateto']))
                    <a href="{{ route('cm.liq') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Liquidation #</th>
                            <th class="px-3 py-3 text-left">Cash Advance</th>
                            <th class="px-3 py-3 text-left">Employee</th>
                            <th class="px-3 py-3 text-left">Liquidation Date</th>
                            <th class="px-3 py-3 text-right">Total Expenses</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-center w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($liquidations as $liquidation)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-1.5 font-medium text-blue-600">
                                    <button type="button" data-drawer-target="drawer-liq-{{ $liquidation->id }}"
                                        data-drawer-show="drawer-liq-{{ $liquidation->id }}" data-drawer-placement="right"
                                        class="text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                                        LIQ-{{ str_pad($liquidation->id, 6, '0', STR_PAD_LEFT) }}
                                    </button>
                                </td>
                                <td class="px-3 py-1.5 text-gray-700">
                                    CA-{{ str_pad($liquidation->cash_advance_id, 6, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-3 py-1.5 text-gray-700">
                                    {{ $liquidation->employee?->last_name ?? '—' }},
                                    {{ $liquidation->employee?->first_name ?? '—' }}
                                </td>
                                <td class="px-3 py-1.5 text-gray-500">
                                    {{ $liquidation->liquidation_date?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-3 py-1.5 text-right tabular-nums text-gray-700">
                                    {{ number_format($liquidation->total_expenses, 2) }}
                                </td>
                                <td class="px-3 py-1.5 text-center">
                                    <span
                                        class="inline-flex rounded-full {{ $liquidation->statusBadgeClass() }} px-2 py-0.5 text-[10px] font-medium">
                                        {{ $liquidation->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-3 py-1.5">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" data-drawer-target="drawer-liq-{{ $liquidation->id }}"
                                            title="View Liquidation" data-drawer-show="drawer-liq-{{ $liquidation->id }}"
                                            data-drawer-placement="right" class="text-gray-500 hover:text-blue-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                <path
                                                    d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                                <path
                                                    d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                            </svg>
                                        </button>
                                        @if (in_array($liquidation->approval_status, ['0', '4']) && $liquidation->employee_id == auth()->id())
                                            <a href="{{ route('cm.liquidation.edit', $liquidation) }}"
                                                title="Edit Liquidation" class="text-gray-500 hover:text-blue-600">
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
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No liquidations found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>
                    Showing {{ $liquidations->firstItem() ?? 0 }}-{{ $liquidations->lastItem() ?? 0 }}
                    of {{ $liquidations->total() }}
                </div>
                {{ $liquidations->links() }}
            </div>
        </div>
    </div>

    {{-- Drawers --}}
    @foreach ($liquidations as $liquidation)
        @php
            $remaining =
                (float) $liquidation->cashAdvance->disbursed_amount -
                (float) $liquidation->cashAdvance->liquidated_amount;
            $excess = max(0, $liquidation->total_expenses - $remaining);
            $hasExcess = $excess > 0;
            $approvalTx = $liquidation->latestApprovalTransaction;
            $stepStatus = $approvalTx ? $approvalTx->status : null;
        @endphp
        <div id="drawer-liq-{{ $liquidation->id }}"
            class="fixed top-0 right-0 z-50 h-screen w-[520px] max-w-full overflow-y-auto bg-white p-5 shadow-xl transition-transform duration-300 transform translate-x-full"
            tabindex="-1" aria-labelledby="drawer-label-{{ $liquidation->id }}" role="dialog">

            <div class="mb-5 flex items-center border-b border-gray-200 pb-4">
                <div class="flex-1">
                    <h5 id="drawer-label-{{ $liquidation->id }}" class="text-lg font-semibold text-gray-900">
                        LIQ-{{ str_pad($liquidation->id, 6, '0', STR_PAD_LEFT) }}
                    </h5>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Liquidation for CA-{{ str_pad($liquidation->cash_advance_id, 6, '0', STR_PAD_LEFT) }}
                    </p>
                </div>
                <button type="button" data-drawer-hide="drawer-liq-{{ $liquidation->id }}"
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
                                class="inline-flex rounded-full {{ $liquidation->statusBadgeClass() }} px-2.5 py-1 text-xs font-medium">
                                {{ $liquidation->statusLabel() }}
                            </span>
                            {{-- @if ($liquidation->approval_status === '1')
                                <span
                                    class="ml-2 inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-medium text-yellow-800">
                                    Pending Approval
                                </span>
                            @endif --}}
                            @if ($hasExcess && $liquidation->approval_status === '2')
                                <span
                                    class="ml-2 inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700">
                                    Reimbursement Pending
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Total Expenses</div>
                        <div class="text-lg font-bold text-gray-900">{{ number_format($liquidation->total_expenses, 2) }}
                        </div>
                    </div>
                </div>
                @if ($hasExcess)
                    <div class="mt-2 rounded-lg bg-indigo-50 p-2 text-xs text-indigo-700">
                        <strong>Excess Amount:</strong> {{ number_format($excess, 2) }}
                        (This will be processed as a reimbursement)
                    </div>
                @endif
            </div>

            <!-- Details -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Details</h6>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500">Cash Advance</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            CA-{{ str_pad($liquidation->cash_advance_id, 6, '0', STR_PAD_LEFT) }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Disbursed: {{ number_format($liquidation->cashAdvance->disbursed_amount ?? 0, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Employee</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $liquidation->employee?->last_name ?? '—' }},
                            {{ $liquidation->employee?->first_name ?? '—' }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $liquidation->employee?->email ?? '' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Liquidation Date</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $liquidation->liquidation_date?->format('M d, Y') ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Submitted</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $liquidation->submitted_at?->format('M d, Y g:i A') ?? '—' }}
                        </p>
                    </div>
                    @if ($liquidation->remarks)
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500">Remarks</label>
                            <p class="mt-1 text-sm text-gray-700">{{ $liquidation->remarks }}</p>
                        </div>
                    @endif
                    <div>
                        <label class="block text-xs text-gray-500">Liquidated Already</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ number_format($liquidation->cashAdvance->liquidated_amount ?? 0, 2) }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Remaining Balance</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ number_format(max(0, $remaining), 2) }}
                        </p>
                    </div>
                    @if ($hasExcess)
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500">Credit Account (Reimbursement Payable)</label>
                            <p class="mt-1 text-sm font-medium text-indigo-600">
                                {{ $liquidation->creditAccount?->account_code ?? '—' }}
                                {{ $liquidation->creditAccount ? '— ' . $liquidation->creditAccount->account_name : '' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Expense Details -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Expense Details</h6>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-3 py-2 text-left">#</th>
                                <th class="px-3 py-2 text-left">Expense Date</th>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-left">GL Account</th>
                                <th class="px-3 py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($liquidation->details as $detail)
                                <tr>
                                    <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 text-gray-600">
                                        {{ $detail->expense_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-800">
                                        {{ $detail->description }}
                                        @if ($detail->reference)
                                            <span class="text-gray-400 text-[10px] block">Ref:
                                                {{ $detail->reference }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="text-gray-700">
                                            {{ $detail->glAccount->account_code ?? '—' }}
                                            {{ $detail->glAccount ? '— ' . $detail->glAccount->account_name : '' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-700">
                                        {{ number_format($detail->amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-center text-gray-500">No expense details
                                        found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t border-gray-200 bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right font-medium">Total</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-900">
                                    {{ number_format($liquidation->total_expenses, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Attachments -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Attachments</h6>
                @if ($liquidation->attachments && $liquidation->attachments->count() > 0)
                    <div class="space-y-2">
                        @foreach ($liquidation->attachments as $attachment)
                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex-shrink-0">
                                        @php
                                            $ext = pathinfo($attachment->original_filename, PATHINFO_EXTENSION);
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
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-700 truncate">
                                            {{ $attachment->original_filename }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ number_format($attachment->file_size / 1024, 1) }} KB
                                            @if ($attachment->description)
                                                · {{ $attachment->description }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2 flex-shrink-0">
                                    <a href="{{ route('cm.liquidation.download-attachment', $attachment->id) }}"
                                        target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                        Download
                                    </a>
                                    @if (in_array($liquidation->approval_status, ['0', '4']) && $liquidation->employee_id == auth()->id())
                                        <form method="POST"
                                            action="{{ route('cm.liquidation.delete-attachment', $attachment->id) }}"
                                            class="inline" onsubmit="return confirm('Delete this attachment?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 hover:text-red-800 text-xs font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-500">No attachments uploaded.</p>
                @endif
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
                        <p class="text-xs text-gray-500">This Liquidation has not been submitted for approval.</p>
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

            @if (in_array($liquidation->approval_status, ['0', '4']) && $liquidation->employee_id == auth()->id())
                <div class="mt-4 border-t border-gray-200 pt-4 text-right">
                    <a href="{{ route('cm.liquidation.edit', $liquidation) }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        Edit Liquidation
                    </a>
                </div>
            @endif
        </div>
    @endforeach
@endsection
