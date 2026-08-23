@extends('dashboard')

@section('title', 'Requests for Disbursement')

@section('content')
    @php
        use Illuminate\Support\Str;
    @endphp
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
                    <h1 class="text-2xl font-bold text-gray-800">Requests for Disbursement</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Create and track disbursement requests through approval.
                    </p>
                </div>

                <a href="{{ route('ap.rfd.create') }}"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                    + New Request
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('ap.rfd') }}"
                class="flex flex-wrap items-center gap-3 border-b px-6 py-4">

                <div class="relative flex-1 min-w-[200px]">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>

                    <input type="text" name="searchvendor" value="{{ request('searchvendor') }}"
                        placeholder="Search by vendor (any line)"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-xs">
                </div>

                <div>
                    <select name="searchapproval" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs">
                        <option value="">All Approval Status</option>
                        <option value="draft" @selected(request('searchapproval') === 'draft')>Draft</option>
                        <option value="pending" @selected(request('searchapproval') === 'pending')>Pending Approval</option>
                        <option value="returned" @selected(request('searchapproval') === 'returned')>Returned</option>
                        <option value="approved" @selected(request('searchapproval') === 'approved')>Approved</option>
                        <option value="rejected" @selected(request('searchapproval') === 'rejected')>Rejected</option>
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

                @if (request()->anyFilled(['searchvendor', 'searchapproval', 'datefrom', 'dateto']))
                    <a href="{{ route('ap.rfd') }}" class="text-xs text-gray-500 hover:text-gray-700">
                        Clear
                    </a>
                @endif
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="border-y border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                @include('partials.sort-link', [
                                    'field' => 'id',
                                    'label' => 'RFD #',
                                ])
                            </th>
                            <th class="px-3 py-3 text-left">Vendor(s)</th>
                            <th class="px-3 py-3 text-left">Request Date</th>
                            <th class="px-3 py-3 text-right">Total Due</th>
                            <th class="px-3 py-3 text-center">Approval Status</th>
                            <th class="px-3 py-3 text-right w-20">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rfds as $rfd)
                            <tr class="hover:bg-gray-50">
                                <!-- RFD Number / Drawer Trigger -->
                                <td
                                    class="px-6 py-3 font-medium text-blue-600 hover:text-blue-800 hover:underline cursor-pointer transition-colors duration-200">
                                    <button type="button" data-drawer-target="drawer-rfd-{{ $rfd->id }}"
                                        data-drawer-show="drawer-rfd-{{ $rfd->id }}" data-drawer-placement="right"
                                        aria-controls="drawer-rfd-{{ $rfd->id }}"
                                        class="text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                                        RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}
                                    </button>
                                </td>

                                <!-- Vendors -->
                                <td class="px-3 py-3 text-gray-700">
                                    {{ $rfd->vendorSummary() }}
                                </td>

                                <!-- Request Date -->
                                <td class="px-3 py-3 text-gray-500">
                                    {{ $rfd->request_date?->format('M d, Y') }}
                                </td>

                                <!-- Total -->
                                <td class="px-3 py-3 text-right tabular-nums text-gray-700">
                                    {{ $rfd->currency->symbol ?? '' }}
                                    {{ number_format($rfd->total_due, 2) }}
                                </td>

                                <!-- Status -->
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full {{ $rfd->statusBadgeClass() }} px-2.5 py-1 text-xs font-medium">
                                        {{ $rfd->statusLabel() }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-3 py-3 text-center">
                                    @if (in_array($rfd->approval_status, ['draft', 'returned', '0', '4']))
                                        <a href="{{ route('ap.rfd.edit', $rfd) }}" title="Edit RFD"
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
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif

                                </td>
                            </tr>

                            {{-- =========================================================
                        RFD DRAWER
                        ========================================================== --}}

                            <div id="drawer-rfd-{{ $rfd->id }}"
                                class="fixed top-0 right-0 z-50 h-screen w-[480px] max-w-full overflow-y-auto bg-white p-5 shadow-xl transition-transform translate-x-full"
                                tabindex="-1" aria-labelledby="drawer-rfd-label-{{ $rfd->id }}">
                                <!-- Drawer Header -->
                                <div class="mb-5 flex items-center border-b border-gray-200 pb-4">
                                    <div class="flex-1">
                                        <h5 id="drawer-rfd-label-{{ $rfd->id }}"
                                            class="text-lg font-semibold text-gray-900">
                                            RFD-{{ str_pad($rfd->id, 6, '0', STR_PAD_LEFT) }}
                                        </h5>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            Request for Disbursement
                                        </p>
                                    </div>

                                    <button type="button" data-drawer-hide="drawer-rfd-{{ $rfd->id }}"
                                        aria-controls="drawer-rfd-{{ $rfd->id }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900">

                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span class="sr-only">Close drawer</span>
                                    </button>
                                </div>

                                {{-- =====================================================
                            STATUS
                            ====================================================== --}}

                                <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-medium text-green-500">
                                                Approval Status
                                            </div>

                                            <div class="mt-1">
                                                <span
                                                    class="inline-flex rounded-full {{ $rfd->statusBadgeClass() }} px-2.5 py-1 text-xs font-medium">
                                                    {{ $rfd->statusLabel() }}
                                                </span>

                                                @if ($rfd->payment_status !== '0')
                                                    <span
                                                        class="inline-flex rounded-full {{ $rfd->paymentStatusBadgeClass() }} px-2.5 py-1 text-xs font-medium">
                                                        {{ $rfd->paymentStatusLabel() }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <div class="text-xs text-green-500">
                                                Total Due
                                            </div>

                                            <div class="text-lg font-bold text-green-700">
                                                {{ $rfd->currency->symbol ?? '' }}
                                                {{ number_format($rfd->total_due, 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- =====================================================
                            REQUEST INFORMATION
                            ====================================================== --}}

                                <div class="mb-6">
                                    <h6 class="mb-3 text-sm font-semibold text-gray-900">
                                        Request Information
                                    </h6>

                                    <div class="grid grid-cols-2 gap-x-4 gap-y-4">
                                        <div>
                                            <label class="block text-xs text-gray-500">
                                                Request Date
                                            </label>

                                            <p class="mt-1 text-sm font-medium text-gray-900">
                                                {{ $rfd->request_date?->format('M d, Y') ?? '—' }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-xs text-gray-500">
                                                Required Date
                                            </label>

                                            <p class="mt-1 text-sm font-medium text-gray-900">
                                                {{ $rfd->required_date?->format('M d, Y') ?? '—' }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-xs text-gray-500">
                                                Currency
                                            </label>

                                            <p class="mt-1 text-sm font-medium text-gray-900">
                                                {{ $rfd->currency->code ?? '—' }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-xs text-gray-500">
                                                Exchange Rate
                                            </label>

                                            <p class="mt-1 text-sm font-medium text-gray-900">
                                                {{ number_format((float) $rfd->exchange_rate, 4) }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-xs text-gray-500">
                                                Payment Terms
                                            </label>

                                            <p class="mt-1 text-sm font-medium text-gray-900">
                                                {{ $rfd->term->name ?? '—' }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-xs text-gray-500">
                                                Payment Method
                                            </label>

                                            <p class="mt-1 text-sm font-medium text-gray-900">
                                                {{ $rfd->paymentMethod->name ?? '—' }}
                                            </p>
                                        </div>

                                    </div>

                                    @if ($rfd->remarks)
                                        <div class="mt-4">
                                            <label class="block text-xs text-gray-500">
                                                Remarks
                                            </label>

                                            <p class="mt-1 text-sm text-gray-700">
                                                {{ $rfd->remarks }}
                                            </p>
                                        </div>
                                    @endif
                                </div>


                                {{-- =====================================================
                            RFD LINES
                            ====================================================== --}}

                                <div class="mb-6">
                                    <div class="mb-3 flex items-center justify-between">
                                        <h6 class="text-sm font-semibold text-gray-900">
                                            Request Lines
                                        </h6>

                                        <span class="text-xs text-gray-500">
                                            {{ $rfd->details->count() }}
                                            {{ $rfd->details->count() === 1 ? 'line' : 'lines' }}
                                        </span>

                                    </div>


                                    <div class="space-y-3">
                                        @forelse ($rfd->details as $detail)
                                            <div class="rounded-lg border border-gray-200 bg-white p-3">

                                                <!-- Line Header -->
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="flex min-w-0 gap-2">
                                                        <div
                                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-gray-100 text-xs font-semibold text-gray-600">
                                                            {{ $detail->line_no }}
                                                        </div>

                                                        <div class="min-w-0">
                                                            <div class="font-medium text-gray-900">
                                                                {{ $detail->description ?: '—' }}
                                                            </div>

                                                            <div class="mt-0.5 text-xs text-gray-500">
                                                                {{ $detail->vendor->name ?? 'No vendor' }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="shrink-0 text-right">
                                                        <div class="text-xs text-gray-500">
                                                            Total
                                                        </div>

                                                        <div class="font-semibold text-gray-900">
                                                            {{ number_format((float) $detail->total_amount, 2) }}
                                                        </div>
                                                    </div>
                                                </div>


                                                <!-- GL / Reference -->
                                                <div class="mt-3 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3">
                                                    <div>
                                                        <label class="block text-xs text-gray-500">
                                                            GL Account
                                                        </label>

                                                        <p class="mt-0.5 text-xs font-medium text-gray-800">
                                                            {{ $detail->glAccount?->getFormattedAccountCodeAttribute() ?? ($detail->glAccount?->account_code ?? '—') }}
                                                        </p>

                                                        @if ($detail->glAccount?->description)
                                                            <p class="text-[11px] text-gray-500">
                                                                {{ $detail->glAccount->description }}
                                                            </p>
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs text-gray-500">
                                                            Reference
                                                        </label>

                                                        <p class="mt-0.5 text-xs font-medium text-gray-800">
                                                            {{ $detail->reference ?? '—' }}
                                                        </p>
                                                    </div>
                                                </div>


                                                <!-- Amounts -->
                                                <div class="mt-3 grid grid-cols-3 gap-2 border-t border-gray-100 pt-3">
                                                    <div>
                                                        <label class="block text-[11px] text-gray-500">
                                                            Amount
                                                        </label>

                                                        <p class="mt-0.5 text-xs text-gray-800">
                                                            {{ number_format((float) $detail->quantity * (float) $detail->unit_price, 2) }}
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <label class="block text-[11px] text-gray-500">
                                                            Discount
                                                        </label>

                                                        <p class="mt-0.5 text-xs text-gray-800">
                                                            {{ number_format((float) $detail->discount_amount, 2) }}
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <label class="block text-[11px] text-gray-500">
                                                            Tax
                                                        </label>

                                                        <p class="mt-0.5 text-xs text-gray-800">
                                                            {{ number_format((float) $detail->tax_amount, 2) }}
                                                        </p>
                                                    </div>
                                                </div>


                                                {{-- Taxes --}}
                                                @if ($detail->taxes->isNotEmpty())
                                                    <div class="mt-3 border-t border-gray-100 pt-3">
                                                        <div class="mb-2 text-xs font-medium text-gray-600">
                                                            Taxes
                                                        </div>

                                                        <div class="space-y-1.5">
                                                            @foreach ($detail->taxes->sortBy('line_no') as $tax)
                                                                <div class="flex items-center justify-between text-xs">
                                                                    <div class="text-gray-600">
                                                                        {{ $tax->tax->name ?? 'Tax' }}
                                                                    </div>

                                                                    <div class="font-medium text-gray-800">
                                                                        {{ number_format((float) $tax->tax_amount, 2) }}
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                        @empty
                                            <div
                                                class="rounded-lg border border-dashed border-gray-300 p-5 text-center text-xs text-gray-500">
                                                No request lines found.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>


                                {{-- =====================================================
                            TOTALS
                            ====================================================== --}}

                                <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <h6 class="mb-3 text-sm font-semibold text-gray-900">
                                        Amount Summary
                                    </h6>

                                    <div class="space-y-2">
                                        <div class="flex justify-between text-xs">
                                            <span class="text-gray-500">Total Amount</span>
                                            <span class="font-medium text-gray-800">
                                                {{ number_format((float) $rfd->total_amount, 2) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-xs">
                                            <span class="text-gray-500">Total Discount</span>
                                            <span class="font-medium text-gray-800">
                                                {{ number_format((float) $rfd->total_discount, 2) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-xs">
                                            <span class="text-gray-500">Total Tax</span>
                                            <span class="font-medium text-gray-800">
                                                {{ number_format((float) $rfd->total_tax, 2) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between border-t border-gray-200 pt-2">
                                            <span class="text-sm font-semibold text-gray-900">
                                                Total Due
                                            </span>

                                            <span class="text-sm font-bold text-gray-900">
                                                {{ $rfd->currency->symbol ?? '' }}
                                                {{ number_format((float) $rfd->total_due, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>


                                {{-- =====================================================
                            APPROVAL WORKFLOW
                            ====================================================== --}}

                                @php
                                    $approvalTx = $rfd->latestApprovalTransaction;
                                @endphp

                                <div class="mb-6">
                                    <h6 class="mb-3 text-sm font-semibold text-gray-900">
                                        Approval Workflow
                                    </h6>

                                    @if ($approvalTx)
                                        <div class="rounded-lg border border-gray-200 p-4">
                                            <!-- Workflow Name -->
                                            <div class="mb-4">
                                                <label class="block text-xs text-gray-500">
                                                    Workflow
                                                </label>

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
                                                    Current Step
                                                </label>

                                                @if ($currentStep)
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
                                                                {{ $currentStep->approverLabel() }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <p class="mt-1 text-sm text-gray-500">
                                                        No current step
                                                    </p>
                                                @endif
                                            </div>


                                            <!-- Workflow Steps -->
                                            @if ($approvalTx->workflow?->steps?->isNotEmpty())
                                                <div class="border-t border-gray-100 pt-3">
                                                    <div class="mb-2 text-xs font-medium text-gray-600">
                                                        Workflow Steps
                                                    </div>

                                                    <div class="space-y-2">
                                                        @foreach ($approvalTx->workflow->steps as $step)
                                                            @php
                                                                $isCurrent =
                                                                    $approvalTx->status === 'pending' &&
                                                                    $approvalTx->current_step_no == $step->step_no;

                                                                $isPast =
                                                                    $approvalTx->current_step_no > $step->step_no ||
                                                                    $approvalTx->status === 'approved';
                                                            @endphp

                                                            <div class="flex items-center gap-2">
                                                                @if ($isPast)
                                                                    <span
                                                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-600">
                                                                        <svg class="h-3.5 w-3.5" fill="none"
                                                                            stroke="currentColor" viewBox="0 0 24 24">

                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M5 13l4 4L19 7" />
                                                                        </svg>

                                                                    </span>
                                                                @elseif ($isCurrent)
                                                                    <span
                                                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                                                        {{ $step->step_no }}
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
                                                                    </div>

                                                                    <div class="text-[11px] text-gray-500">
                                                                        {{ $step->approverLabel() }}
                                                                    </div>
                                                                </div>

                                                                @if ($isCurrent)
                                                                    <span
                                                                        class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700">
                                                                        Current
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="rounded-lg border border-dashed border-gray-300 p-4 text-center">
                                            <p class="text-xs text-gray-500">
                                                This RFD has not been submitted for approval.
                                            </p>
                                        </div>
                                    @endif
                                </div>


                                {{-- =====================================================
                            APPROVAL HISTORY
                            ====================================================== --}}

                                @if ($approvalTx && $approvalTx->histories->isNotEmpty())
                                    <div class="mb-6">
                                        <h6 class="mb-3 text-sm font-semibold text-gray-900">
                                            Approval History
                                        </h6>

                                        <div class="rounded-lg border border-gray-200 p-4">
                                            @foreach ($approvalTx->histories->sortByDesc('acted_at') as $history)
                                                <div class="relative flex gap-3">
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
                                                                <svg class="h-3.5 w-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            @elseif ($history->action === 'rejected')
                                                                <svg class="h-3.5 w-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            @elseif ($history->action === 'returned')
                                                                <svg class="h-3.5 w-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                                                </svg>
                                                            @else
                                                                <span class="text-[10px] font-bold">
                                                                    {{-- {{ $history->step_no }} --}} S
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>


                                                    <div class="min-w-0 flex-1 pb-2">
                                                        <div class="flex items-start justify-between gap-2">
                                                            <div>
                                                                <div
                                                                    class="text-xs font-semibold capitalize text-gray-800">
                                                                    {{ $history->action }}
                                                                </div>

                                                                <div class="text-[11px] text-gray-500">
                                                                    {{ $history->actor
                                                                        ? trim($history->actor->last_name . ', ' . $history->actor->first_name . ' ' . ($history->actor->middle_name ?? ''))
                                                                        : 'System' }}
                                                                </div>
                                                            </div>

                                                            <div class="shrink-0 text-[10px] text-gray-400">
                                                                {{ $history->acted_at }}
                                                            </div>
                                                        </div>

                                                        @if ($history->remarks)
                                                            <div
                                                                class="mt-1 rounded-md bg-gray-50 p-2 text-xs text-gray-600">
                                                                {{ $history->remarks }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif


                                {{-- =====================================================
                            ACTION
                            ====================================================== --}}

                                @if (in_array($rfd->approval_status, ['draft', 'returned', '0', '4']))
                                    <div class="mt-6 border-t border-gray-200 pt-4">
                                        <a href="{{ route('ap.rfd.edit', $rfd->id) }}"
                                            class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                                            Edit RFD
                                            <svg class="ms-2 h-4 w-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 12H5m14 0-4 4m4-4-4-4" />
                                            </svg>
                                        </a>
                                    </div>
                                @endif
                            </div>

                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <img src="{{ asset('images/rfd.svg') }}" alt="No requests for disbursement"
                                        class="mx-auto mb-4 h-24 w-24">
                                    No requests for disbursement found. Click
                                    <a href="{{ route('ap.rfd.create') }}" class="text-blue-600 hover:underline">
                                        here
                                    </a>
                                    to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


            <!-- Pagination -->
            <div class="flex items-center justify-between border-t px-6 py-4 text-xs text-gray-500">
                <div>
                    Showing {{ $rfds->firstItem() ?? 0 }}-{{ $rfds->lastItem() ?? 0 }}
                    of {{ $rfds->total() }}
                </div>
                {{ $rfds->links() }}
            </div>
        </div>
    </div>


@endsection
