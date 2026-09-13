@extends('dashboard')

@section('title', $cashAdvance->exists ? 'Edit Cash Advance' : 'New Cash Advance')

@section('content')
    @php

    @endphp
    <div class="mx-auto max-w-7xl">
        @if ($errors->any())
            <div id="error-alert" class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 shadow-sm">
                <div class="font-semibold text-red-700 text-sm">Please correct the following errors:</div>
                <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!$hasApprover)
            <div class="mt-3 mb-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800">
                <div class="flex items-center">
                    <svg class="h-5 w-5 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.334-.213 2.98-1.742 2.98H3.48c-1.53 0-2.492-1.646-1.743-2.98l6.518-11.59zM10 13a1 1 0 100-2 1 1 0 000 2zm-1-8a1 1 0 011 1v3a1 1 0 11-2 0V6a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <span><strong>Warning:</strong> No approver has been configured for Cash Advances. You can save a draft,
                        but you will not be able to submit for approval until an approver is set up.</span>
                </div>
            </div>
        @endif

        @php
            $isEdit = $cashAdvance->exists;
            $formAction = $isEdit ? route('cm.ca.update', $cashAdvance->id) : route('cm.ca.store');
        @endphp

        <form method="POST" action="{{ $formAction }}">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="submit_for_approval" x-data x-init="document.querySelector('form').addEventListener('submit', function(e) {
                if (e.submitter && e.submitter.dataset.submit === 'approve') {
                    document.querySelector('[name=submit_for_approval]').value = '1';
                } else {
                    document.querySelector('[name=submit_for_approval]').value = '0';
                }
            })">

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">
                                {{ $isEdit ? 'Edit Cash Advance' : 'New Cash Advance' }}
                            </h1>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ $isEdit ? 'Update your cash advance request' : 'Request a cash advance for business expenses' }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('cm.ca') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20">
                                Back
                            </a>
                            <button type="submit" data-submit="draft"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 hover:bg-white">
                                Save Draft
                            </button>
                            <button type="submit" data-submit="approve"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg"
                                {{ $hasApprover ? '' : 'disabled' }}
                                title="{{ $hasApprover ? '' : 'Approver must be configured first' }}">
                                Submit for Approval
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="px-8 py-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                                <input type="number" step="0.01" min="0.01" name="amount"
                                    value="{{ old('amount', $cashAdvance->amount) }}" required
                                    class="w-full rounded-lg border border-gray-300 pl-8 pr-4 py-2.5 text-sm text-right focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            </div>
                            @error('amount')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                GL Debit Account <span class="text-red-500">*</span>
                                <span class="text-xs text-gray-500">(The GL account to which this cash advance will be
                                    charged.)</span>
                            </label>
                            <select name="gl_account_id" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                <option value="">— Select Account —</option>
                                @foreach ($glAccounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('gl_account_id', $cashAdvance->gl_account_id) == $account->id)>
                                        {{ $account->account_code }} — {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('gl_account_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Expected Liquidation Date
                            </label>
                            <input type="date" name="expected_liquidation_date" min="{{ now()->format('Y-m-d') }}"
                                value="{{ old('expected_liquidation_date', optional($cashAdvance->expected_liquidation_date)->format('Y-m-d')) }}"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            @error('expected_liquidation_date')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Purpose <span class="text-red-500">*</span>
                            </label>
                            <textarea name="purpose" rows="3" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder="Explain the reason for this cash advance">{{ old('purpose', $cashAdvance->purpose) }}</textarea>
                            @error('purpose')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <span class="mt-1 text-xs text-blue-900">Journal Preview</span><span class="mt-1 text-xs text-blue-500">
                        (bank account selected at disbursement)</span><br>
                    <span class="mt-1 text-xs text-blue-500">
                        Dr Advances to Employees ...
                        <span id="journal-debit-amount">0.00</span>
                    </span>
                    <br>

                    <span class="text-xs text-blue-500">
                        &nbsp;&nbsp;&nbsp;&nbsp;Cr [Bank Account - selected at disbursement] ...
                        <span id="journal-credit-amount">0.00</span>
                    </span>
                    @if ($isEdit)
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-xs text-gray-500">
                                <strong>Status:</strong> {{ $cashAdvance->statusLabel() }}
                                @if ($approverComments)
                                    <br><strong>Approver Remarks:</strong>
                                    {{ $approverComments->histories->last()->remarks }} -
                                    {{ $approverComments->histories->last()->acted_at->format('M d, Y g:i A') }}
                                @endif
                                @if ($cashAdvance->submitted_at)
                                    <br><strong>Submitted:</strong>
                                    {{ $cashAdvance->submitted_at->format('M d, Y g:i A') }}
                                @endif
                                @if ($cashAdvance->liquidated_amount > 0)
                                    <br><strong>Liquidated:</strong>
                                    {{ number_format($cashAdvance->liquidated_amount, 2) }}
                                    of {{ number_format($cashAdvance->amount, 2) }}
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.querySelector('input[name="amount"]');
            const debitAmount = document.getElementById('journal-debit-amount');
            const creditAmount = document.getElementById('journal-credit-amount');

            function updateJournalPreview() {
                const amount = parseFloat(amountInput.value) || 0;

                const formatted = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                }).format(amount);

                debitAmount.textContent = formatted;
                creditAmount.textContent = formatted;
            }

            amountInput.addEventListener('input', updateJournalPreview);

            updateJournalPreview();
        });
    </script>
@endsection
