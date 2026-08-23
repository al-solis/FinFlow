@extends('dashboard')

@section('title', 'Cash Disbursement Dashboard')

@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('success'))
            <div class="mt-3 mb-3 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-5 mb-5">
            <h1 class="text-2xl font-bold text-gray-800">Cash Disbursement</h1>
            <p class="mt-1 text-sm text-gray-500">Review and process disbursement requests</p>
        </div>

        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                <div class="text-sm font-medium text-blue-700">Pending Approval</div>
                <div class="mt-1 text-2xl font-bold text-blue-900">{{ $pendingApproval }}</div>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                <div class="text-sm font-medium text-green-700">Awaiting Disbursement</div>
                <div class="mt-1 text-2xl font-bold text-green-900">{{ $awaitingDisbursement }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="text-sm font-medium text-gray-500">Total Requests</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $rfds->total() }}</div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Reference</th>
                            <th class="px-3 py-3 text-left">Type</th>
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
                                <td class="px-6 py-3 font-medium text-blue-600">
                                    <button type="button" data-drawer-target="drawer-pay-{{ $rfd->id }}"
                                        data-drawer-show="drawer-pay-{{ $rfd->id }}" data-drawer-placement="right"
                                        class="text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                                        RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}
                                    </button>
                                </td>
                                <td class="px-3 py-3"><span
                                        class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">RFD</span></td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-gray-900">
                                        {{ $rfd->creator->last_name . ', ' . $rfd->creator->first_name ?? '—' }}</div>
                                </td>
                                <td class="px-3 py-3 text-gray-700">
                                    {{ $rfd->remarks ?: '—' }}
                                    <div class="text-gray-400">{{ $rfd->details->count() }} line
                                        item{{ $rfd->details->count() === 1 ? '' : 's' }}</div>
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format($rfd->total_due, 2) }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if ($rfd->payment_status == 1)
                                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Disbursed</span>
                                    @elseif ($rfd->payment_status == 2)
                                        <span class="rounded-full bg-orange-50 px-2.5 py-1 text-orange-700">Partially
                                            Disbursed</span>
                                    @else
                                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">Approved</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <button type="button" data-drawer-target="drawer-pay-{{ $rfd->id }}"
                                        data-drawer-show="drawer-pay-{{ $rfd->id }}" data-drawer-placement="right"
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                                        View
                                    </button>
                                    @if ($rfd->payment_status != 1)
                                        <button type="button" data-drawer-target="drawer-pay-{{ $rfd->id }}"
                                            data-drawer-show="drawer-pay-{{ $rfd->id }}" data-drawer-placement="right"
                                            class="ml-1 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                                            Disburse →
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">No approved requests
                                    awaiting disbursement.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @foreach ($rfds as $rfd)
                    <!-- Disbursement drawer -->
                    <div id="drawer-pay-{{ $rfd->id }}"
                        class="fixed top-0 right-0 z-50 h-screen w-[440px] max-w-full overflow-y-auto bg-white p-5 shadow-xl transition-transform translate-x-full">
                        <div class="mb-4 flex items-start justify-between">
                            <div>
                                <span
                                    class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">Approved</span>
                                <span
                                    class="ml-1 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">Request
                                    for Disbursement</span>
                                <h5 class="mt-2 text-lg font-semibold text-gray-900">
                                    {{ $rfd->remarks ?: 'Disbursement Request' }}</h5>
                                <p class="text-xs text-gray-500">RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">Total Amount</div>
                                <div class="text-lg font-bold text-gray-900">
                                    {{ number_format($rfd->total_due, 2) }}</div>
                            </div>
                        </div>

                        <div class="mb-5 rounded-lg bg-gray-50 p-4 text-xs space-y-1.5">
                            <div class="flex justify-between"><span class="text-gray-500">Requested By</span><span
                                    class="font-medium">{{ $rfd->creator->last_name . ', ' . $rfd->creator->first_name ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-gray-500">Date Submitted</span><span
                                    class="font-medium">{{ $rfd->submitted_at?->format('n/j/Y, g:i A') }}</span>
                            </div>
                        </div>

                        <div class="mb-5">
                            <h6 class="mb-2 text-xs font-semibold uppercase text-gray-500">Line Items
                                ({{ $rfd->details->count() }})
                            </h6>
                            <div class="overflow-hidden rounded-lg border border-gray-200">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-gray-50 text-gray-500">
                                        <tr>
                                            <th class="px-2 py-2 text-left">#</th>
                                            <th class="px-2 py-2 text-left">Vendor</th>
                                            <th class="px-2 py-2 text-left">Description</th>
                                            <th class="px-2 py-2 text-left">Account</th>
                                            <th class="px-2 py-2 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($rfd->details as $detail)
                                            <tr>
                                                <td class="px-2 py-2 text-gray-400">{{ $detail->line_no }}</td>
                                                <td class="px-2 py-2 text-gray-700">
                                                    {{ $detail->vendor->name ?? '—' }}</td>
                                                <td class="px-2 py-2">{{ $detail->description }}</td>
                                                <td class="px-2 py-2 font-medium text-blue-700">
                                                    {{ $detail->glAccount->getFormattedAccountCodeAttribute() ?? '—' }}
                                                </td>
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
                            <div class="mb-3 space-y-4">
                                <h6 class="text-xs font-semibold text-gray-500">
                                    Disburse Funds — {{ $openInvoicesByVendor->count() }}
                                    vendor{{ $openInvoicesByVendor->count() === 1 ? '' : 's' }} pending
                                </h6>

                                @foreach ($openInvoicesByVendor as $vendorId => $invoices)
                                    @php $vendor = $invoices->first()->vendor; @endphp

                                    <form method="POST" action="{{ route('cm.cash.disbursement.disburse', $rfd->id) }}"
                                        class="rounded-lg border border-green-200 bg-green-50 p-4" x-data="paymentMethodFields()">
                                        @csrf

                                        <div class="mb-3 flex items-center justify-between">
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">
                                                    {{ $vendor->name ?? 'Vendor #' . $vendorId }}</div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $invoices->count() }}
                                                    invoice{{ $invoices->count() === 1 ? '' : 's' }}
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xs text-gray-500">Amount Due</div>
                                                <div class="text-sm font-bold text-green-800">
                                                    {{ number_format($invoices->sum('amount_due'), 2) }}
                                                </div>
                                            </div>
                                        </div>

                                        @foreach ($invoices as $invoice)
                                            <input type="hidden" name="ap_invoice_ids[]" value="{{ $invoice->id }}">
                                        @endforeach

                                        <!-- Payment Method drives which fields below are required -->
                                        <label class="mb-1 block text-xs font-medium text-gray-700">
                                            Payment Method <span class="text-red-500">*</span>
                                        </label>
                                        <select name="payment_method_id" required @change="updateMethod($event.target)"
                                            class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                                            <option value="">— Select —</option>
                                            @foreach ($paymentMethods ?? [] as $method)
                                                <option value="{{ $method->id }}"
                                                    data-requires-bank="{{ $method->requires_bank ? 1 : 0 }}"
                                                    data-requires-check="{{ $method->requires_check ? 1 : 0 }}"
                                                    data-requires-reference="{{ $method->requires_reference_no ? 1 : 0 }}"
                                                    data-allows-partial="{{ $method->allow_partial_payment ? 1 : 0 }} ">
                                                    {{ $method->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <!-- Bank/Cash account — always available, label adapts, required only
                                                                     when the method flags it, though the posting engine will still need
                                                                     one selected to know which GL account to credit -->
                                        <label class="mb-1 block text-xs font-medium text-gray-700">
                                            <span
                                                x-text="requiresBank ? 'Pay From (Bank Account)' : 'Pay From (Cash/Bank Account)'"></span>
                                            <span x-show="requiresBank" class="text-red-500">*</span>
                                        </label>
                                        <select name="bank_account_id" :required="requiresBank"
                                            class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                                            <option value="">— Select —</option>
                                            @foreach ($bankAccounts ?? [] as $bank)
                                                <option value="{{ $bank->id }}">{{ $bank->name }} —
                                                    {{ $bank->account_number }}</option>
                                            @endforeach
                                        </select>

                                        <!-- Reference / Check Number — label and requirement follow the method -->
                                        <label class="mb-1 block text-xs font-medium text-gray-700">
                                            <span x-text="requiresCheck ? 'Check Number' : 'Reference Number'"></span>
                                            <span x-show="requiresReferenceNo || requiresCheck"
                                                class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="reference_number"
                                            :required="requiresReferenceNo || requiresCheck"
                                            placeholder="Transaction / check number"
                                            class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">

                                        <!-- Check Date — only shown/required for check-type methods -->
                                        <div x-show="requiresCheck" x-cloak>
                                            <label class="mb-1 block text-xs font-medium text-gray-700">
                                                Check Date <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" name="check_date" :required="requiresCheck"
                                                class="mb-3 w-full rounded-lg border border-gray-300 p-2.5 text-xs">
                                        </div>

                                        <button type="submit"
                                            class="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700">
                                            Confirm Disbursement — {{ $vendor->name ?? 'Vendor' }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @else
                            <div
                                class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-xs text-gray-500">
                                This request has been fully disbursed.
                            </div>
                        @endif

                        <div class="mt-4 border-t border-gray-200 pt-4 text-right">
                            <button type="button" data-drawer-hide="drawer-pay-{{ $rfd->id }}"
                                class="text-xs font-medium text-gray-500 hover:text-gray-700">Close</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        function paymentMethodFields() {
            return {
                requiresBank: false,
                requiresCheck: false,
                requiresReferenceNo: false,

                updateMethod(selectEl) {
                    const opt = selectEl.options[selectEl.selectedIndex];
                    this.requiresBank = opt?.dataset.requiresBank === '1';
                    this.requiresCheck = opt?.dataset.requiresCheck === '1';
                    this.requiresReferenceNo = opt?.dataset.requiresReference === '1';
                },
            };
        }
    </script>
@endsection
