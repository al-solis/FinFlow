@extends('dashboard')

@section('title', 'Review Refund Request')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-8 py-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            REF-{{ str_pad($refund->id, 6, '0', STR_PAD_LEFT) }}
                        </h1>
                        <p class="mt-1 text-sm text-purple-100">
                            Refund for CA-{{ str_pad($refund->cash_advance_id, 6, '0', STR_PAD_LEFT) }} —
                            Step: {{ $step->step_name }}
                            @if ($step->is_final_approval)
                                <span class="ml-2 rounded bg-white/20 px-2 py-0.5 text-xs font-medium">Final Approval</span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('approvals.index') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                        Back to Inbox
                    </a>
                </div>
            </div>

            <!-- Refund Information -->
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-xs text-gray-500">Employee</div>
                        <div class="font-medium text-gray-800">
                            {{ $refund->employee?->last_name ?? '—' }},
                            {{ $refund->employee?->first_name ?? '—' }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ $refund->employee?->email ?? '' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Cash Advance</div>
                        <div class="font-medium text-gray-800">
                            CA-{{ str_pad($refund->cash_advance_id, 6, '0', STR_PAD_LEFT) }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Amount: {{ number_format($refund->cashAdvance->amount ?? 0, 2) }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Liquidated: {{ number_format($refund->cashAdvance->liquidated_amount ?? 0, 2) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Refund Amount</div>
                        <div class="font-bold text-purple-700 text-lg">{{ number_format($refund->amount, 2) }}</div>
                    </div>
                    <div class="col-span-2 md:col-span-3">
                        <div class="text-xs text-gray-500">Purpose</div>
                        <div class="font-medium text-gray-800">{{ $refund->purpose ?? '—' }}</div>
                    </div>
                    @if ($refund->submitted_at)
                        <div>
                            <div class="text-xs text-gray-500">Submitted</div>
                            <div class="font-medium text-gray-800">{{ $refund->submitted_at->format('M d, Y g:i A') }}
                            </div>
                        </div>
                    @endif
                    <div>
                        <div class="text-xs text-gray-500">CA Balance After Refund</div>
                        <div class="font-medium text-gray-800">
                            {{ number_format(($refund->cashAdvance->amount ?? 0) - ($refund->cashAdvance->liquidated_amount ?? 0) - $refund->amount, 2) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">GL Account</div>
                        <div class="font-medium text-gray-800">
                            {{ $refund->glAccount?->getFormattedAccountCodeAttribute() ?? '—' }}
                            {{ $refund->glAccount ? '— ' . $refund->glAccount->account_name : '' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permission Banner -->
            <div class="px-8 pt-4 flex flex-wrap gap-2 text-xs">
                @if ($step->can_edit_chart_of_account)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit GL account</span>
                @endif
                @if ($step->can_edit_amount)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit amount</span>
                @endif
                @if (!$step->can_edit_chart_of_account && !$step->can_edit_amount)
                    <span class="rounded-full bg-gray-50 px-2.5 py-1 text-gray-500">View only — no edits at this step</span>
                @endif
            </div>

            <!-- Approval Form -->
            <form method="POST" action="{{ route('cm.refund.approve', $transaction->id) }}" class="px-8 py-6"
                id="approve-form">
                @csrf

                <!-- GL Account - Editable if step allows -->
                <div class="mb-4">
                    @if ($step->can_edit_chart_of_account)
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            GL DEBIT Account <span class="text-red-500">*</span>
                        </label>
                        <select name="gl_account_id" required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                            <option value="">— Select Account —</option>
                            @foreach ($glAccounts as $account)
                                <option value="{{ $account->id }}" @selected(old('gl_account_id', $refund->gl_account_id ?? $refund->cashAdvance?->gl_account_id) == $account->id)>
                                    {{ $account->account_code }} — {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-purple-600">You have permission to edit the GL account</p>
                        {{-- @else
                        <div class="font-medium text-gray-800 py-2.5">
                            {{ $refund->glAccount?->account_code ?? ($refund->cashAdvance?->glAccount?->account_code ?? '—') }}
                            {{ $refund->glAccount ?? $refund->cashAdvance?->glAccount ? '— ' . ($refund->glAccount?->account_name ?? $refund->cashAdvance?->glAccount?->account_name) : '' }}
                        </div>
                        <input type="hidden" name="gl_account_id"
                            value="{{ $refund->gl_account_id ?? $refund->cashAdvance?->gl_account_id }}">
                        <p class="mt-1 text-xs text-gray-500">GL account cannot be edited at this step</p> --}}
                    @endif
                    @error('gl_account_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Amount - Editable if step allows -->
                <div class="mb-4">
                    @if ($step->can_edit_amount)
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Refund Amount <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                            <input type="number" step="0.01" min="0.01" name="amount"
                                value="{{ old('amount', $refund->amount) }}" required
                                class="w-full rounded-lg border border-gray-300 pl-8 pr-4 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        </div>
                        <p class="mt-1 text-xs text-purple-600">You have permission to edit the amount</p>
                        {{-- @else
                        <div class="font-bold text-purple-700 text-lg py-2.5">
                            {{ number_format($refund->amount, 2) }}
                        </div>
                        <input type="hidden" name="amount" value="{{ $refund->amount }}">
                        <p class="mt-1 text-xs text-gray-500">Amount cannot be edited at this step</p> --}}
                    @endif
                    @error('amount')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remarks -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Remarks <span id="remarks-required" class="text-red-500 hidden">*</span>
                    </label>
                    <textarea name="remarks" id="remarks" rows="2" placeholder="Optional notes for this approval"
                        class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500"></textarea>
                    <p id="remarks-error" class="mt-1 text-xs text-red-600 hidden">Remarks are required for rejection or
                        return.</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5">
                    @if ($step->can_return_to_requester)
                        <button type="button" onclick="submitWithCheck('return')"
                            class="rounded-lg border border-orange-300 bg-white px-5 py-2.5 text-sm font-medium text-orange-700 hover:bg-orange-50">
                            Return to Requester
                        </button>
                    @endif
                    <button type="button" onclick="submitWithCheck('reject')"
                        class="rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50">
                        Reject
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-purple-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-purple-700">
                        {{ $step->is_final_approval ? 'Approve & Post' : 'Approve & Forward' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function submitWithCheck(action) {
            const form = document.getElementById('approve-form');
            const remarks = document.getElementById('remarks');
            const remarksRequired = document.getElementById('remarks-required');
            const remarksError = document.getElementById('remarks-error');

            // Check if remarks is empty
            if (!remarks.value.trim()) {
                remarksRequired.classList.remove('hidden');
                remarksError.classList.remove('hidden');
                remarks.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                remarks.focus();
                return;
            }

            // Hide error if remarks is filled
            remarksRequired.classList.add('hidden');
            remarksError.classList.add('hidden');
            remarks.classList.remove('border-red-500', 'ring-1', 'ring-red-500');

            // Set the form action based on the action type
            if (action === 'return') {
                form.action = "{{ route('cm.refund.return', $transaction->id) }}";
            } else if (action === 'reject') {
                form.action = "{{ route('cm.refund.reject', $transaction->id) }}";
            }

            form.submit();
        }

        // Remove error state when user starts typing in remarks
        document.getElementById('remarks').addEventListener('input', function() {
            if (this.value.trim()) {
                document.getElementById('remarks-required').classList.add('hidden');
                document.getElementById('remarks-error').classList.add('hidden');
                this.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
            }
        });
    </script>
@endsection
