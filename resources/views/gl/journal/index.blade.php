@extends('dashboard')

@section('title', 'Journal Entries')

@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div id="success-alert"
                class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800 shadow-sm">
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
                    <h1 class="text-2xl font-bold text-gray-800">Journal Entries</h1>
                    <p class="mt-1 text-sm text-gray-500">Create and manage general journal entries</p>
                </div>
                <a href="{{ route('gl.journals.create') }}"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    + New Journal Entry
                </a>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-5 px-6 py-4 border-b border-gray-200">
                <div class="rounded-lg border border-gray-200 bg-white p-3 text-center">
                    <div class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Total</div>
                    <div class="mt-0.5 text-lg font-bold text-gray-900">{{ $stats['total'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
                    <div class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Draft</div>
                    <div class="mt-0.5 text-lg font-bold text-gray-600">{{ $stats['draft'] }}</div>
                </div>
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-center">
                    <div class="text-[10px] font-medium text-yellow-700 uppercase tracking-wider">Pending</div>
                    <div class="mt-0.5 text-lg font-bold text-yellow-800">{{ $stats['pending'] }}</div>
                </div>
                <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-center">
                    <div class="text-[10px] font-medium text-green-700 uppercase tracking-wider">Posted</div>
                    <div class="mt-0.5 text-lg font-bold text-green-800">{{ $stats['posted'] }}</div>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-center col-span-2 sm:col-span-1">
                    <div class="text-[10px] font-medium text-red-700 uppercase tracking-wider">Rejected</div>
                    <div class="mt-0.5 text-lg font-bold text-red-800">{{ $stats['rejected'] }}</div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('gl.journal') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">
                <div>
                    <select name="searchstatus" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">All Status</option>
                        <option value="0" @selected(request('searchstatus') === '0')>Draft</option>
                        <option value="1" @selected(request('searchstatus') === '1')>Pending</option>
                        <option value="2" @selected(request('searchstatus') === '2')>Posted</option>
                        <option value="3" @selected(request('searchstatus') === '3')>Rejected</option>
                        <option value="4" @selected(request('searchstatus') === '4')>Returned</option>
                    </select>
                </div>
                <div>
                    <select name="searchtype" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">All Types</option>
                        <option value="adjustment" @selected(request('searchtype') === 'adjustment')>Adjustment</option>
                        <option value="approval_posting" @selected(request('searchtype') === 'approval_posting')>Approval/Posting</option>
                        <option value="bank_statement_import" @selected(request('searchtype') === 'bank_statement_import')>Bank Statement Import</option>
                        <option value="cash_advance" @selected(request('searchtype') === 'cash_advance')>Cash Advance</option>
                        <option value="cash_advance_disbursement" @selected(request('searchtype') === 'cash_advance_disbursement')>Cash Advance Disbursement
                        </option>
                        <option value="disbursement" @selected(request('searchtype') === 'disbursement')>Disbursement</option>
                        <option value="liquidation" @selected(request('searchtype') === 'liquidation')>Liquidation</option>
                        <option value="manual" @selected(request('searchtype') === 'manual')>Manual</option>
                        <option value="refund_approval" @selected(request('searchtype') === 'refund_approval')>Refund Approval</option>
                        <option value="reimbursement_approval" @selected(request('searchtype') === 'reimbursement_approval')>Reimbursement Approval</option>
                        <option value="reimbursement_payment" @selected(request('searchtype') === 'reimbursement_payment')>Reimbursement Payment</option>
                        <option value="reversal" @selected(request('searchtype') === 'reversal')>Reversal</option>
                    </select>
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
                @if (request()->anyFilled(['searchstatus', 'searchtype', 'datefrom', 'dateto']))
                    <a href="{{ route('gl.journal') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Journal #</th>
                            <th class="px-3 py-3 text-left">Date</th>
                            <th class="px-3 py-3 text-left">Type</th>
                            <th class="px-3 py-3 text-left">Description</th>
                            <th class="px-3 py-3 text-right">Debit</th>
                            <th class="px-3 py-3 text-right">Credit</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($journals as $journal)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-blue-600">
                                    <button type="button" data-drawer-target="drawer-journal-{{ $journal->id }}"
                                        data-drawer-show="drawer-journal-{{ $journal->id }}"
                                        data-drawer-placement="right"
                                        class="text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                                        {{ $journal->journal_no }}
                                    </button>
                                </td>
                                <td class="px-3 py-3 text-gray-500">
                                    {{ $journal->journal_date->format('M d, Y') }}
                                </td>
                                <td class="px-3 py-3">
                                    <span
                                        class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 capitalize">
                                        {{ $journal->journal_type }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-gray-700 max-w-xs truncate">
                                    {{ $journal->description ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">
                                    {{ number_format($journal->total_debit, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">
                                    {{ number_format($journal->total_credit, 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full {{ $journal->statusBadgeClass() }} px-2 py-0.5 text-[10px] font-medium">
                                        {{ $journal->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex items-center justify-end gap-1">
                                        @if (in_array($journal->approval_status, ['0', '4']) &&
                                                ($journal->created_by == Auth::id() || Auth::user()->role_id == 1))
                                            <a href="{{ route('gl.journals.edit', $journal) }}" title="Edit"
                                                class="text-gray-500 hover:text-blue-600">
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
                                        @if (in_array($journal->approval_status, ['0', '4']) &&
                                                ($journal->created_by == Auth::id() || Auth::user()->role_id == 1))
                                            <form method="POST" action="{{ route('gl.journals.destroy', $journal) }}"
                                                class="inline" onsubmit="return confirm('Delete this journal entry?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Delete"
                                                    class="text-gray-500 hover:text-red-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z" />
                                                        <path
                                                            d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    No journal entries found.
                                    <a href="{{ route('gl.journals.create') }}"
                                        class="text-blue-600 hover:underline">Create your first journal entry</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>
                    Showing {{ $journals->firstItem() ?? 0 }}-{{ $journals->lastItem() ?? 0 }}
                    of {{ $journals->total() }}
                </div>
                {{ $journals->links() }}
            </div>
        </div>
    </div>

    {{-- Drawers --}}
    @foreach ($journals as $journal)
        @php
            $approvalTx = $journal->latestApprovalTransaction;
            $stepStatus = $approvalTx ? $approvalTx->status : null;
        @endphp
        <div id="drawer-journal-{{ $journal->id }}"
            class="fixed top-0 right-0 z-50 h-screen w-[520px] max-w-full overflow-y-auto bg-white p-5 shadow-xl transition-transform translate-x-full"
            tabindex="-1">
            <div class="mb-5 flex items-center border-b border-gray-200 pb-4">
                <div class="flex-1">
                    <h5 class="text-lg font-semibold text-gray-900">
                        {{ $journal->journal_no }}
                    </h5>
                    <p class="mt-0.5 text-xs text-gray-500">Journal Entry</p>
                </div>
                <button type="button" data-drawer-hide="drawer-journal-{{ $journal->id }}"
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
                                class="inline-flex rounded-full {{ $journal->statusBadgeClass() }} px-2.5 py-1 text-xs font-medium">
                                {{ $journal->statusLabel() }}
                            </span>
                            @if ($journal->approval_status === '1')
                                <span
                                    class="ml-2 inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-medium text-yellow-800">
                                    Pending Approval
                                </span>
                            @endif
                            @if ($journal->approval_status === '2')
                                <span
                                    class="ml-2 inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">
                                    Posted
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Date</div>
                        <div class="text-sm font-medium text-gray-900">{{ $journal->journal_date->format('M d, Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Details</h6>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500">Journal No</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $journal->journal_no }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Type</label>
                        <p class="mt-1 text-sm font-medium text-gray-900 capitalize">{{ $journal->journal_type }}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500">Description</label>
                        <p class="mt-1 text-sm text-gray-700">{{ $journal->description ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Created By</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $journal->creator?->last_name . ', ' . $journal->creator?->first_name ?? '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Submitted</label>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $journal->submitted_at?->format('M d, Y g:i A') ?? '—' }}
                        </p>
                    </div>
                    @if ($journal->posted_at)
                        <div>
                            <label class="block text-xs text-gray-500">Posted</label>
                            <p class="mt-1 text-sm font-medium text-gray-900">
                                {{ $journal->posted_at->format('M d, Y g:i A') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Lines -->
            <div class="mb-6">
                <h6 class="mb-3 text-sm font-semibold text-gray-900">Journal Lines</h6>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-3 py-2 text-left">#</th>
                                <th class="px-3 py-2 text-left">Account</th>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-right">Debit</th>
                                <th class="px-3 py-2 text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($journal->lines as $line)
                                <tr>
                                    <td class="px-3 py-2 text-gray-400">{{ $line->line_no }}</td>
                                    <td class="px-3 py-2 font-medium text-blue-700">
                                        {{ $line->glAccount->getFormattedAccountCodeAttribute() ?? '—' }}
                                        <div class="text-[10px] text-gray-500">{{ $line->glAccount->account_name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ $line->description ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        {{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        {{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-gray-200 bg-gray-50 font-bold">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right">Total</td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    {{ number_format($journal->total_debit, 2) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    {{ number_format($journal->total_credit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
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
                            <p class="mt-1 text-sm font-medium text-gray-900">{{ $approvalTx->workflow->name ?? '—' }}</p>
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
                        <p class="text-xs text-gray-500">This journal entry has not been submitted for approval.</p>
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

            <!-- Action Buttons -->
            @if (in_array($journal->approval_status, ['0', '4']) &&
                    ($journal->created_by == Auth::id() || Auth::user()->role_id == 1))
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <a href="{{ route('gl.journals.edit', $journal) }}"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        Edit Journal Entry
                    </a>
                </div>
            @endif

            @if ($journal->approval_status === '1')
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <div class="rounded-lg bg-yellow-50 p-3 text-center text-xs text-yellow-800">
                        <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.334-.213 2.98-1.742 2.98H3.48c-1.53 0-2.492-1.646-1.743-2.98l6.518-11.59zM10 13a1 1 0 100-2 1 1 0 000 2zm-1-8a1 1 0 011 1v3a1 1 0 11-2 0V6a1 1 0 011-1z"
                                clip-rule="evenodd" />
                        </svg>
                        This journal entry is currently pending approval.
                    </div>
                </div>
            @endif

            @if ($journal->approval_status === '2')
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <div class="rounded-lg bg-green-50 p-3 text-center text-xs text-green-800">
                        <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        This journal entry has been posted.
                        @if ($journal->posted_at)
                            <br><span class="text-[10px]">Posted on
                                {{ $journal->posted_at->format('M d, Y g:i A') }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endforeach
@endsection
