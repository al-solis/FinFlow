@extends('dashboard')

@section('title', 'Cash Disbursement Dashboard')

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

        @if ($errors->any())
            <div id="error-alert" class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-xs text-red-700">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 mb-5">
            <h1 class="text-2xl font-bold text-gray-800">Cash Disbursement</h1>
            <p class="mt-1 text-sm text-gray-500">Review and process disbursement requests for RFDs, Cash Advances, and
                Reimbursements</p>
        </div>

        <!-- Stats -->
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                <div class="text-sm font-medium text-blue-700">Pending Approval</div>
                <div class="mt-1 text-2xl font-bold text-blue-900">{{ $pendingApproval }}</div>
            </div>
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5">
                <div class="text-sm font-medium text-yellow-700">Awaiting Disbursement</div>
                <div class="mt-1 text-2xl font-bold text-yellow-900">{{ $awaitingDisbursement }}</div>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                <div class="text-sm font-medium text-green-700">Total RFDs</div>
                <div class="mt-1 text-2xl font-bold text-green-900">{{ $rfds->total() }}</div>
            </div>
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-5">
                <div class="text-sm font-medium text-purple-700">Cash Advances Ready</div>
                <div class="mt-1 text-2xl font-bold text-purple-900">{{ $cashAdvances->count() }}</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-4 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="disbursement-tabs" role="tablist">
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 rounded-t-lg text-blue-600 border-blue-600 active"
                        id="tab-rfd" data-tab-target="content-rfd" type="button" role="tab"
                        aria-controls="content-rfd" aria-selected="true">
                        RFD Disbursements
                        @if (isset($rfds) && $rfds->count() > 0)
                            <span
                                class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-green-600 rounded-full">
                                {{ $rfds->count() }}
                            </span>
                        @endif
                    </button>
                </li>
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300"
                        id="tab-ca" data-tab-target="content-ca" type="button" role="tab" aria-controls="content-ca"
                        aria-selected="false">
                        Cash Advances
                        @if (isset($cashAdvances) && $cashAdvances->count() > 0)
                            <span
                                class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-purple-600 rounded-full">
                                {{ $cashAdvances->count() }}
                            </span>
                        @endif
                    </button>
                </li>
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300"
                        id="tab-reim" data-tab-target="content-reim" type="button" role="tab"
                        aria-controls="content-reim" aria-selected="false">
                        Reimbursements
                        @if (isset($reimbursements) && $reimbursements->count() > 0)
                            <span
                                class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-indigo-600 rounded-full">
                                {{ $reimbursements->count() }}
                            </span>
                        @endif
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tab Content: RFDs -->
        <div id="content-rfd" class="rounded-xl border border-gray-200 bg-white shadow-sm" role="tabpanel"
            aria-labelledby="tab-rfd">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Reference</th>
                            <th class="px-3 py-3 text-left">Requestor</th>
                            <th class="px-3 py-3 text-left">Purpose</th>
                            <th class="px-3 py-3 text-right">Amount</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rfds as $rfd)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-green-600">
                                    <button type="button" data-drawer-target="drawer-rfd-{{ $rfd->id }}"
                                        data-drawer-show="drawer-rfd-{{ $rfd->id }}" data-drawer-placement="right"
                                        class="text-green-600 hover:text-green-800 hover:underline transition-colors">
                                        RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}
                                    </button>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-gray-900">
                                        {{ $rfd->creator->last_name . ', ' . $rfd->creator->first_name ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-gray-700">
                                    {{ $rfd->remarks ?: '—' }}
                                    <div class="text-gray-400">{{ $rfd->details->count() }} line item(s)</div>
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format($rfd->total_due, 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($rfd->payment_status == 1)
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-700">Disbursed</span>
                                    @elseif ($rfd->payment_status == 2)
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-medium text-yellow-700">Partially
                                            Disbursed</span>
                                    @else
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700">Ready</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right">
                                    @if ($rfd->payment_status != 1)
                                        <button type="button" data-drawer-target="drawer-rfd-{{ $rfd->id }}"
                                            data-drawer-show="drawer-rfd-{{ $rfd->id }}"
                                            data-drawer-placement="right"
                                            class="rounded-lg bg-green-600 px-3 py-3 text-xs font-medium text-white hover:bg-green-700">
                                            Disburse
                                        </button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">No RFDs awaiting
                                    disbursement.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                    <div>Showing {{ $rfds->firstItem() ?? 0 }}-{{ $rfds->lastItem() ?? 0 }} of {{ $rfds->total() }}</div>
                    {{ $rfds->links() }}
                </div>
            </div>
        </div>

        <!-- Tab Content: Cash Advances -->
        <div id="content-ca" class="hidden rounded-xl border border-gray-200 bg-white shadow-sm mb-2" role="tabpanel"
            aria-labelledby="tab-ca">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">CA #</th>
                            <th class="px-3 py-3 text-left">Employee</th>
                            <th class="px-3 py-3 text-left">Purpose</th>
                            <th class="px-3 py-3 text-right">Amount</th>
                            <th class="px-3 py-3 text-right">Disbursed</th>
                            <th class="px-3 py-3 text-right">Remaining</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($cashAdvances as $ca)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-purple-600">
                                    <button type="button" data-drawer-target="drawer-ca-{{ $ca->id }}"
                                        data-drawer-show="drawer-ca-{{ $ca->id }}" data-drawer-placement="right"
                                        class="text-purple-600 hover:text-purple-800 hover:underline transition-colors">
                                        CA-{{ str_pad($ca->id, 6, '0', STR_PAD_LEFT) }}
                                    </button>
                                </td>
                                <td class="px-3 py-3 text-gray-700">
                                    {{ $ca->employee?->last_name ?? '—' }}, {{ $ca->employee?->first_name ?? '—' }}
                                </td>
                                <td class="px-3 py-3 text-gray-700 max-w-xs truncate">
                                    {{ Str::limit($ca->purpose, 50) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-700">
                                    {{ number_format($ca->amount, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums text-gray-700">
                                    {{ number_format($ca->disbursed_amount ?? 0, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums font-medium text-green-600">
                                    {{ number_format($ca->remaining_amount, 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($ca->isFullyDisbursed())
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-700">Fully
                                            Disbursed</span>
                                    @elseif ($ca->disbursed_amount > 0)
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-medium text-yellow-700">Partially
                                            Disbursed</span>
                                    @else
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700">Ready</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right">
                                    @if (!$ca->isFullyDisbursed())
                                        <button type="button" data-drawer-target="drawer-ca-{{ $ca->id }}"
                                            data-drawer-show="drawer-ca-{{ $ca->id }}"
                                            data-drawer-placement="right"
                                            class="rounded-lg bg-purple-600 px-3 py-3 text-xs font-medium text-white hover:bg-purple-700">
                                            Disburse
                                        </button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">No Cash Advances awaiting
                                    disbursement.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: Reimbursements -->
        <div id="content-reim" class="hidden rounded-xl border border-gray-200 bg-white shadow-sm mb-2" role="tabpanel"
            aria-labelledby="tab-reim">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Reimbursement #</th>
                            <th class="px-3 py-3 text-left">Employee</th>
                            <th class="px-3 py-3 text-left">Source</th>
                            <th class="px-3 py-3 text-left">Purpose</th>
                            <th class="px-3 py-3 text-right">Amount</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($reimbursements as $reimbursement)
                            @php
                                $isFromLiquidation = $reimbursement->isFromLiquidation();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-indigo-600">
                                    <button type="button" data-drawer-target="drawer-reim-{{ $reimbursement->id }}"
                                        data-drawer-show="drawer-reim-{{ $reimbursement->id }}"
                                        data-drawer-placement="right"
                                        class="text-indigo-600 hover:text-indigo-800 hover:underline transition-colors">
                                        REIM-{{ str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT) }}
                                    </button>
                                </td>
                                <td class="px-3 py-3 text-gray-700">
                                    {{ $reimbursement->employee?->last_name ?? '—' }},
                                    {{ $reimbursement->employee?->first_name ?? '—' }}
                                </td>
                                <td class="px-3 py-3">
                                    @if ($isFromLiquidation)
                                        <span
                                            class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                            LIQ-{{ str_pad($reimbursement->liquidation_id, 6, '0', STR_PAD_LEFT) }}
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-500">
                                            Manual
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-gray-700 max-w-xs truncate">
                                    {{ Str::limit($reimbursement->purpose ?? '—', 50) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums font-medium text-gray-700">
                                    {{ number_format($reimbursement->amount, 2) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if (!$reimbursement->isPaid() && !$reimbursement->totalReimbursementDisbursedAmount())
                                        <span
                                            class="inline-flex rounded-full {{ $reimbursement->statusBadgeClass() }} px-2 py-0.5 text-[10px] font-medium">
                                            {{ $reimbursement->statusLabel() }}
                                        </span>
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-medium text-yellow-700">
                                            Ready for Payment
                                        </span>
                                    @endif
                                    @if (!$reimbursement->isPaid() && $reimbursement->totalReimbursementDisbursedAmount() > 0)
                                        <span
                                            class="ml-1 inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-medium text-yellow-700">
                                            Partially Disbursed
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right">
                                    @if (!$reimbursement->isPaid())
                                        <button type="button" data-drawer-target="drawer-reim-{{ $reimbursement->id }}"
                                            data-drawer-show="drawer-reim-{{ $reimbursement->id }}"
                                            data-drawer-placement="right"
                                            class="rounded-lg bg-indigo-600 px-3 py-3 text-xs font-medium text-white hover:bg-indigo-700">
                                            Disburse
                                        </button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">No Reimbursements awaiting
                                    disbursement.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RFD Drawers -->
        @foreach ($rfds as $rfd)
            <div id="drawer-rfd-{{ $rfd->id }}"
                class="fixed top-0 right-0 z-50 h-screen w-[480px] max-w-full overflow-y-auto bg-white p-5 shadow-xl transition-transform translate-x-full">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <span
                            class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">Approved</span>
                        <span
                            class="ml-1 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">RFD</span>
                        <h5 class="mt-2 text-lg font-semibold text-gray-900">{{ $rfd->remarks ?: 'Disbursement Request' }}
                        </h5>
                        <p class="text-xs text-gray-500">RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Total Amount</div>
                        <div class="text-lg font-bold text-gray-900">{{ number_format($rfd->total_due, 2) }}</div>
                    </div>
                </div>

                <div class="mb-5 rounded-lg bg-gray-50 p-4 text-xs">
                    <div class="flex justify-between"><span class="text-gray-500">Requested By</span><span
                            class="font-medium">{{ $rfd->creator->last_name . ', ' . $rfd->creator->first_name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Date Submitted</span><span
                            class="font-medium">{{ $rfd->submitted_at?->format('n/j/Y, g:i A') }}</span></div>
                </div>

                <!-- Line Items -->
                <div class="mb-5">
                    <h6 class="mb-2 text-xs font-semibold uppercase text-gray-500">Line Items
                        ({{ $rfd->details->count() }})</h6>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="px-2 py-2 text-left">#</th>
                                    <th class="px-2 py-2 text-left">Vendor</th>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($rfd->details as $detail)
                                    <tr>
                                        <td class="px-2 py-2 text-gray-400">{{ $detail->line_no }}</td>
                                        <td class="px-2 py-2 text-gray-700">{{ $detail->vendor->name ?? '—' }}</td>
                                        <td class="px-2 py-2">{{ $detail->description }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ number_format($detail->total_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @php
                    $openInvoicesByVendor = $rfd
                        ->apInvoices()
                        ->where('status', '!=', 'paid')
                        ->with('vendor')
                        ->get()
                        ->groupBy('vendor_id');
                @endphp

                @if ($openInvoicesByVendor->isNotEmpty())
                    @foreach ($openInvoicesByVendor as $vendorId => $invoices)
                        @php
                            $vendor = $invoices->first()->vendor;
                            $vendorAmountDue = (float) $invoices->sum('amount_due');
                        @endphp

                        <form method="POST" action="{{ route('cm.cash.disbursement.disburse', $rfd->id) }}"
                            class="rounded-lg border border-green-200 bg-green-50 p-4 mb-3" x-data="paymentMethodFields({{ $vendorAmountDue }})">
                            @csrf

                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $vendor->name ?? 'Vendor #' . $vendorId }}</div>
                                    <div class="text-xs text-gray-500">{{ $invoices->count() }} invoice(s)</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">Amount Due</div>
                                    <div class="text-sm font-bold text-green-800">{{ number_format($vendorAmountDue, 2) }}
                                    </div>
                                </div>
                            </div>

                            @foreach ($invoices as $invoice)
                                <input type="hidden" name="ap_invoice_ids[]" value="{{ $invoice->id }}">
                            @endforeach

                            <label class="mb-1 block text-xs font-medium text-gray-700">Payment Method <span
                                    class="text-red-500">*</span></label>
                            <select name="payment_method_id" required @change="updateMethod($event.target)"
                                class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                                <option value="">— Select —</option>
                                @foreach ($paymentMethods ?? [] as $method)
                                    <option value="{{ $method->id }}"
                                        data-requires-bank="{{ $method->requires_bank ? 1 : 0 }}"
                                        data-requires-check="{{ $method->requires_check ? 1 : 0 }}"
                                        data-requires-reference="{{ $method->requires_reference_no ? 1 : 0 }}"
                                        data-allows-partial="{{ $method->allow_partial_payment ? 1 : 0 }}">
                                        {{ $method->name }}
                                    </option>
                                @endforeach
                            </select>

                            <label class="mb-1 block text-xs font-medium text-gray-700">Amount to Pay <span
                                    class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0.01" :max="amountDue"
                                name="amount_to_pay" x-model.number="amountToPay" @input="clampAmount()"
                                :readonly="!allowsPartial"
                                :class="!allowsPartial ? 'bg-gray-100 text-gray-600' : 'bg-white'"
                                class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs text-right">

                            <label class="mb-1 block text-xs font-medium text-gray-700">Bank Account <span
                                    x-show="requiresBank" class="text-red-500">*</span></label>
                            <select name="bank_account_id" :required="requiresBank"
                                class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                                <option value="">— Select —</option>
                                @foreach ($bankAccounts ?? [] as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }} —
                                        {{ $bank->account_number }}</option>
                                @endforeach
                            </select>

                            <label class="mb-1 block text-xs font-medium text-gray-700">Reference Number <span
                                    x-show="requiresReferenceNo || requiresCheck" class="text-red-500">*</span></label>
                            <input type="text" name="reference_number" :required="requiresReferenceNo || requiresCheck"
                                placeholder="Transaction / check number"
                                class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">

                            <div x-show="requiresCheck" x-cloak>
                                <label class="mb-1 block text-xs font-medium text-gray-700">Check Date <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="check_date" :required="requiresCheck"
                                    class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                            </div>

                            <button type="submit"
                                class="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700">
                                Confirm Disbursement
                            </button>
                        </form>
                    @endforeach
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-xs text-gray-500">
                        This request has been fully disbursed.
                    </div>
                @endif

                <div class="mt-4 border-t border-gray-200 pt-4 text-right">
                    <button type="button" data-drawer-hide="drawer-rfd-{{ $rfd->id }}"
                        class="text-xs font-medium text-gray-500 hover:text-gray-700">Close</button>
                </div>
            </div>
        @endforeach

        <!-- Cash Advance Drawers -->
        @foreach ($cashAdvances as $ca)
            <div id="drawer-ca-{{ $ca->id }}"
                class="fixed top-0 right-0 z-50 h-screen w-[480px] max-w-full overflow-y-auto bg-white p-5 shadow-xl transition-transform translate-x-full">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <span
                            class="inline-flex rounded-full bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-700">Cash
                            Advance</span>
                        <h5 class="mt-2 text-lg font-semibold text-gray-900">{{ $ca->purpose ?: 'Cash Advance Request' }}
                        </h5>
                        <p class="text-xs text-gray-500">CA-{{ str_pad($ca->id, 6, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Remaining Amount</div>
                        <div class="text-lg font-bold text-purple-700">{{ number_format($ca->remaining_amount, 2) }}</div>
                    </div>
                </div>

                <div class="mb-5 rounded-lg bg-gray-50 p-4 text-xs">
                    <div class="flex justify-between"><span class="text-gray-500">Employee</span><span
                            class="font-medium">{{ $ca->employee?->last_name ?? '—' }},
                            {{ $ca->employee?->first_name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Total Amount</span><span
                            class="font-medium">{{ number_format($ca->amount, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Already Disbursed</span><span
                            class="font-medium">{{ number_format($ca->disbursed_amount ?? 0, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">GL Account</span><span
                            class="font-medium">{{ $ca->glAccount?->account_name ?? '—' }}</span></div>
                </div>

                <form method="POST" action="{{ route('cm.cash.disbursement.disburse.ca', $ca->id) }}"
                    class="rounded-lg border border-purple-200 bg-purple-50 p-4" x-data="paymentMethodFields({{ $ca->remaining_amount }})">
                    @csrf

                    <div class="mb-3">
                        <div class="text-sm font-semibold text-gray-900">Disburse Cash Advance</div>
                        <div class="text-xs text-gray-500">Remaining: {{ number_format($ca->remaining_amount, 2) }}</div>
                    </div>

                    <label class="mb-1 block text-xs font-medium text-gray-700">Payment Method <span
                            class="text-red-500">*</span></label>
                    <select name="payment_method_id" required @change="updateMethod($event.target)"
                        class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        <option value="">— Select —</option>
                        @foreach ($paymentMethods ?? [] as $method)
                            <option value="{{ $method->id }}"
                                data-requires-bank="{{ $method->requires_bank ? 1 : 0 }}"
                                data-requires-check="{{ $method->requires_check ? 1 : 0 }}"
                                data-requires-reference="{{ $method->requires_reference_no ? 1 : 0 }}"
                                data-allows-partial="{{ $method->allow_partial_payment ? 1 : 0 }}">
                                {{ $method->name }}
                            </option>
                        @endforeach
                    </select>

                    <label class="mb-1 block text-xs font-medium text-gray-700">Amount to Pay <span
                            class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0.01" :max="amountDue" name="amount_to_pay"
                        x-model.number="amountToPay" @input="clampAmount()" :readonly="!allowsPartial"
                        :class="!allowsPartial ? 'bg-gray-100 text-gray-600' : 'bg-white'"
                        class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs text-right">

                    <label class="mb-1 block text-xs font-medium text-gray-700">Bank Account <span x-show="requiresBank"
                            class="text-red-500">*</span></label>
                    <select name="bank_account_id" :required="requiresBank"
                        class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        <option value="">— Select —</option>
                        @foreach ($bankAccounts ?? [] as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->name }} — {{ $bank->account_number }}
                            </option>
                        @endforeach
                    </select>

                    <label class="mb-1 block text-xs font-medium text-gray-700">Reference Number <span
                            x-show="requiresReferenceNo || requiresCheck" class="text-red-500">*</span></label>
                    <input type="text" name="reference_number" :required="requiresReferenceNo || requiresCheck"
                        placeholder="Transaction / check number"
                        class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">

                    <div x-show="requiresCheck" x-cloak>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Check Date <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="check_date" :required="requiresCheck"
                            class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                    </div>

                    <button type="submit"
                        class="w-full rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-purple-700">
                        Confirm Disbursement
                    </button>
                </form>

                <div class="mt-4 border-t border-gray-200 pt-4 text-right">
                    <button type="button" data-drawer-hide="drawer-ca-{{ $ca->id }}"
                        class="text-xs font-medium text-gray-500 hover:text-gray-700">Close</button>
                </div>
            </div>
        @endforeach

        <!-- Reimbursement Drawers -->
        @foreach ($reimbursements as $reimbursement)
            <div id="drawer-reim-{{ $reimbursement->id }}"
                class="fixed top-0 right-0 z-50 h-screen w-[480px] max-w-full overflow-y-auto bg-white p-5 shadow-xl transition-transform translate-x-full">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <span
                            class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700">Reimbursement</span>
                        @if ($reimbursement->isFromLiquidation())
                            <span
                                class="ml-1 inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                From LIQ-{{ str_pad($reimbursement->liquidation_id, 6, '0', STR_PAD_LEFT) }}
                            </span>
                        @endif
                        <h5 class="mt-2 text-lg font-semibold text-gray-900">
                            {{ $reimbursement->purpose ?: 'Reimbursement Request' }}</h5>
                        <p class="text-xs text-gray-500">REIM-{{ str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Amount</div>
                        <div class="text-lg font-bold text-indigo-700">{{ number_format($reimbursement->amount, 2) }}
                        </div>
                    </div>
                </div>

                <div class="mb-5 rounded-lg bg-gray-50 p-4 text-xs">
                    <div class="flex justify-between"><span class="text-gray-500">Employee</span><span
                            class="font-medium">{{ $reimbursement->employee?->last_name ?? '—' }},
                            {{ $reimbursement->employee?->first_name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Cash Advance</span><span
                            class="font-medium">CA-{{ str_pad($reimbursement->cash_advance_id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Credit Account</span><span
                            class="font-medium">{{ $reimbursement->glAccount?->account_name ?? '—' }}</span></div>
                    @if ($reimbursement->isFromLiquidation())
                        <div class="flex justify-between"><span class="text-gray-500">Source Liquidation</span><span
                                class="font-medium">LIQ-{{ str_pad($reimbursement->liquidation_id, 6, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    @endif
                </div>

                @php
                    $totalDisbursed = $reimbursement->totalReimbursementDisbursedAmount();
                    $amountToPay = max(0, $reimbursement->amount - $totalDisbursed);
                @endphp

                <form method="POST"
                    action="{{ route('cm.cash.disbursement.disburse.reimbursement', $reimbursement->id) }}"
                    class="rounded-lg border border-indigo-200 bg-indigo-50 p-4" x-data="paymentMethodFields({{ $amountToPay }})">
                    @csrf

                    <div class="mb-3">
                        <div class="text-sm font-semibold text-gray-900">Disburse Reimbursement</div>
                        <div class="text-xs text-gray-500">Amount:
                            {{ number_format($amountToPay, 2) }}
                        </div>
                    </div>

                    <label class="mb-1 block text-xs font-medium text-gray-700">Payment Method <span
                            class="text-red-500">*</span></label>
                    <select name="payment_method_id" required @change="updateMethod($event.target)"
                        class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        <option value="">— Select —</option>
                        @foreach ($paymentMethods ?? [] as $method)
                            <option value="{{ $method->id }}"
                                data-requires-bank="{{ $method->requires_bank ? 1 : 0 }}"
                                data-requires-check="{{ $method->requires_check ? 1 : 0 }}"
                                data-requires-reference="{{ $method->requires_reference_no ? 1 : 0 }}"
                                data-allows-partial="{{ $method->allow_partial_payment ? 1 : 0 }}">
                                {{ $method->name }}
                            </option>
                        @endforeach
                    </select>

                    <label class="mb-1 block text-xs font-medium text-gray-700">Amount to Pay <span
                            class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0.01" :max="amountToPay" name="amount_to_pay"
                        x-model.number="amountToPay" @input="clampAmount()" :readonly="!allowsPartial"
                        :class="!allowsPartial ? 'bg-gray-100 text-gray-600' : 'bg-white'"
                        class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs text-right">

                    <label class="mb-1 block text-xs font-medium text-gray-700">Bank Account <span x-show="requiresBank"
                            class="text-red-500">*</span></label>
                    <select name="bank_account_id" :required="requiresBank"
                        class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                        <option value="">— Select —</option>
                        @foreach ($bankAccounts ?? [] as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->name }} — {{ $bank->account_number }}
                            </option>
                        @endforeach
                    </select>

                    <label class="mb-1 block text-xs font-medium text-gray-700">Reference Number <span
                            x-show="requiresReferenceNo || requiresCheck" class="text-red-500">*</span></label>
                    <input type="text" name="reference_number" :required="requiresReferenceNo || requiresCheck"
                        placeholder="Transaction / check number"
                        class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">

                    <div x-show="requiresCheck" x-cloak>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Check Date <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="check_date" :required="requiresCheck"
                            class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                    </div>

                    <button type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                        Confirm Disbursement
                    </button>
                </form>

                <div class="mt-4 border-t border-gray-200 pt-4 text-right">
                    <button type="button" data-drawer-hide="drawer-reim-{{ $reimbursement->id }}"
                        class="text-xs font-medium text-gray-500 hover:text-gray-700">Close</button>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function paymentMethodFields(amountDue) {
            return {
                requiresBank: false,
                requiresCheck: false,
                requiresReferenceNo: false,
                allowsPartial: true,
                amountDue: amountDue,
                amountToPay: amountDue,

                updateMethod(selectEl) {
                    const opt = selectEl.options[selectEl.selectedIndex];
                    this.requiresBank = opt?.dataset.requiresBank === '1';
                    this.requiresCheck = opt?.dataset.requiresCheck === '1';
                    this.requiresReferenceNo = opt?.dataset.requiresReference === '1';
                    this.allowsPartial = opt?.dataset.allowsPartial === '1';

                    if (!this.allowsPartial) {
                        this.amountToPay = this.amountDue;
                    }
                },

                clampAmount() {
                    let val = parseFloat(this.amountToPay);
                    if (isNaN(val) || val <= 0) {
                        val = 0;
                    }
                    if (val > this.amountDue) {
                        val = this.amountDue;
                    }
                    this.amountToPay = val;
                },
            };
        }

        // Tab switching
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('[data-tab-target]');
            const tabContents = {
                'content-rfd': document.getElementById('content-rfd'),
                'content-ca': document.getElementById('content-ca'),
                'content-reim': document.getElementById('content-reim')
            };

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    document.querySelectorAll('[data-tab-target]').forEach(t => {
                        t.classList.remove('text-blue-600', 'border-blue-600');
                        t.classList.add('hover:text-gray-600', 'hover:border-gray-300');
                        t.setAttribute('aria-selected', 'false');
                    });

                    // Add active class to clicked tab
                    this.classList.add('text-blue-600', 'border-blue-600');
                    this.classList.remove('hover:text-gray-600', 'hover:border-gray-300');
                    this.setAttribute('aria-selected', 'true');

                    // Hide all contents
                    Object.values(tabContents).forEach(content => {
                        if (content) content.classList.add('hidden');
                    });

                    // Show target content
                    const targetId = this.getAttribute('data-tab-target');
                    const targetContent = document.getElementById(targetId);
                    if (targetContent) {
                        targetContent.classList.remove('hidden');
                    }
                });
            });
        });
    </script>
@endsection
