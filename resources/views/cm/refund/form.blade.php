@extends('dashboard')

@section('title', $isEdit ? 'Edit Refund' : 'New Refund')

@section('content')
    <div class="mx-auto max-w-4xl">
        @if ($errors->any())
            <div id="error-alert" class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 shadow-sm">
                <div class="font-semibold text-red-700 text-sm">Please correct the following errors:</div>
                <ul class="mt-1 list-disc list-inside text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-8 py-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            {{ $isEdit ? 'Edit Refund' : 'New Refund' }}
                        </h1>
                        <p class="mt-1 text-sm text-purple-100">
                            @if ($cashAdvance)
                                For CA-{{ str_pad($cashAdvance->id, 6, '0', STR_PAD_LEFT) }} — Excess:
                                {{ number_format($excessAmount, 2) }}
                            @else
                                Create a refund request
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('cm.ref') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20">
                        Back
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ $isEdit ? route('cm.refund.update', $refund) : route('cm.refund.store') }}"
                class="px-8 py-6">
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
                <input hidden type="text" name="cash_advance_id" value="{{ $cashAdvance?->id }}">

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Amount <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                            <input type="number" step="0.01" min="0.01" name="amount"
                                value="{{ old('amount', $refund->amount ?? ($excessAmount ?? 0)) }}" required
                                class="w-full rounded-lg border border-gray-300 pl-8 pr-4 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        </div>
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Purpose <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="purpose" value="{{ old('purpose', $refund->purpose ?? '') }}" required
                            placeholder="Reason for refund"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        @error('purpose')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            GL Debit Account <span class="text-red-500">*</span>
                        </label>
                        <select name="gl_account_id" required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                            <option value="">— Select Account —</option>
                            @foreach ($glAccounts as $account)
                                <option value="{{ $account->id }}" @selected(old('gl_account_id', $refund->gl_account_id ?? $cashAdvance?->gl_account_id) == $account->id)>
                                    {{ $account->account_code }} — {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('gl_account_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Select the GL Debit account for this refund transaction. e.g.,
                            (Cash in Bank)</p>
                    </div>

                    @if ($cashAdvance)
                        <div class="md:col-span-2">
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <p class="text-sm text-gray-600">
                                    <strong>Cash Advance:</strong> CA-{{ str_pad($cashAdvance->id, 6, '0', STR_PAD_LEFT) }}
                                    — Amount: {{ number_format($cashAdvance->amount, 2) }} |
                                    Liquidated: {{ number_format($cashAdvance->liquidated_amount, 2) }} |
                                    <span class="text-purple-700 font-semibold">Excess:
                                        {{ number_format($excessAmount, 2) }}</span>
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($isEdit)
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">
                            <strong>Status:</strong> {{ $refund->statusLabel() }}
                            @if ($refund->submitted_at)
                                <br><strong>Submitted:</strong> {{ $refund->submitted_at->format('M d, Y g:i A') }}
                            @endif
                        </p>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5 mt-6">
                    <a href="{{ route('cm.ref') }}"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" data-submit="draft"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Save Draft
                    </button>
                    <button type="submit" data-submit="approve"
                        class="rounded-lg bg-purple-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-purple-700">
                        Submit for Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
