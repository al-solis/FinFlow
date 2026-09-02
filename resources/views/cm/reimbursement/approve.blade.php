@extends('dashboard')

@section('title', 'Review Reimbursement Request')

@section('content')
    <div class="mx-auto max-w-5xl" x-data="reimbursementApproval()">
        @if ($errors->any())
            <div class="mt-3 mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-xs text-red-700">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            REIM-{{ str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT) }}
                        </h1>
                        <p class="mt-1 text-sm text-indigo-100">
                            @if ($reimbursement->isFromLiquidation())
                                Auto-generated from LIQ-{{ str_pad($reimbursement->liquidation_id, 6, '0', STR_PAD_LEFT) }}
                                —
                            @endif
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

            <!-- Employee Information -->
            <div class="px-8 py-6 border-b border-gray-200 grid grid-cols-2 gap-3 md:grid-cols-5 text-xs">
                <div>
                    <div class="text-gray-500">Employee</div>
                    <div class="font-medium text-gray-800">
                        {{ $reimbursement->employee?->last_name ?? '—' }},
                        {{ $reimbursement->employee?->first_name ?? '—' }}
                    </div>
                    <div class="text-gray-400">{{ $reimbursement->employee?->email ?? '' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Total Amount</div>
                    <div class="font-bold text-indigo-700 text-lg">{{ number_format($reimbursement->amount, 2) }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Cash Advance</div>
                    <div class="font-medium text-gray-800">
                        CA-{{ str_pad($reimbursement->cash_advance_id, 6, '0', STR_PAD_LEFT) }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">Submitted</div>
                    <div class="font-medium text-gray-800">
                        {{ $reimbursement->submitted_at?->format('M d, Y g:i A') ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">Purpose</div>
                    <div class="font-medium text-gray-800">{{ $reimbursement->purpose ?? '—' }}</div>
                </div>
                @if ($reimbursement->isFromLiquidation())
                    <div class="col-span-2 md:col-span-5">
                        <div class="rounded-lg border border-blue-300 bg-blue-50 px-3 py-2 text-blue-800 text-xs">
                            <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            This reimbursement was auto-generated from an excess liquidation.
                            The credit account below was selected during liquidation approval.
                        </div>
                    </div>
                @endif
            </div>

            <!-- Permission Banner -->
            <div class="px-8 pt-4 flex flex-wrap gap-2 text-xs">
                @if ($step->can_edit_chart_of_account)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit GL accounts</span>
                @endif
                @if ($step->can_edit_amount)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit amounts</span>
                @endif
                @if (!$step->can_edit_chart_of_account && !$step->can_edit_amount)
                    <span class="rounded-full bg-gray-50 px-2.5 py-1 text-gray-500">View only — no edits at this step</span>
                @endif
            </div>

            <!-- Line Items -->
            <div class="px-8 py-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Expense Details</h2>

                <form method="POST" action="{{ route('cm.reimbursement.approve', $transaction->id) }}" id="approve-form">
                    @csrf
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">#</th>
                                    <th class="px-3 py-2 text-left">Expense Date</th>
                                    <th class="px-3 py-2 text-left">Description</th>
                                    <th class="px-3 py-2 text-left">Debit Account</th>
                                    <th class="px-3 py-2 text-left">Reference</th>
                                    <th class="px-3 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reimbursement->details as $detail)
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-2 text-gray-500 whitespace-nowrap">
                                            {{ $detail->expense_date?->format('M d, Y') }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-800">{{ $detail->description }}</td>

                                        <!-- GL account: editable only if step allows -->
                                        <td class="px-3 py-2">
                                            @if ($step->can_edit_chart_of_account)
                                                <select name="lines[{{ $detail->id }}][gl_account_id]"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                                    <option value="">Select account&hellip;</option>
                                                    @foreach ($expenseAccounts as $account)
                                                        <option value="{{ $account->id }}" @selected($detail->gl_account_id == $account->id)>
                                                            {{ $account->account_code }} &mdash;
                                                            {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="text-gray-700">
                                                    {{ $detail->glAccount->account_code ?? '—' }}
                                                    {{ $detail->glAccount ? '— ' . $detail->glAccount->account_name : '' }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-3 py-2 text-gray-500">{{ $detail->reference ?: '—' }}</td>

                                        <!-- Amount: editable only if step allows amount edits -->
                                        <td class="px-3 py-2 text-right">
                                            @if ($step->can_edit_amount)
                                                <input type="number" step="0.01" min="0.01"
                                                    name="lines[{{ $detail->id }}][amount]"
                                                    x-model.number="lines[{{ $loop->index }}].amount"
                                                    @input="recalcTotal()"
                                                    class="w-28 rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                {{ number_format($detail->amount, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 font-medium text-gray-700">
                                <tr>
                                    <td colspan="5" class="px-3 py-2 text-right text-sm">Total</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sm font-bold"
                                        x-text="formatNumber(total)">
                                        {{ number_format($reimbursement->amount, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Credit Account Section (Reimbursement Payable) -->
                    <div class="mt-4 p-4 border border-indigo-200 bg-indigo-50 rounded-lg">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="h-5 w-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-indigo-800">Reimbursement Payable Account</h4>
                                <p class="text-xs text-indigo-600">
                                    This is the credit account that will be used for the reimbursement payable.
                                </p>
                                <div class="mt-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Credit Account (Reimbursement Payable) <span class="text-red-500">*</span>
                                    </label>
                                    @if ($step->can_edit_chart_of_account)
                                        <select name="credit_account_id" required
                                            class="w-full rounded-lg border border-indigo-300 bg-white px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            <option value="">— Select Reimbursement Payable Account —</option>
                                            @foreach ($creditAccounts as $account)
                                                <option value="{{ $account->id }}" @selected(old('credit_account_id', $reimbursement->gl_account_id) == $account->id)>
                                                    {{ $account->account_code }} — {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-indigo-600">You have permission to edit the credit
                                            account</p>
                                    @else
                                        <div class="font-medium text-gray-800 py-2.5">
                                            {{ $reimbursement->glAccount?->account_code ?? '—' }}
                                            {{ $reimbursement->glAccount ? '— ' . $reimbursement->glAccount->account_name : '' }}
                                        </div>
                                        <input type="hidden" name="credit_account_id"
                                            value="{{ $reimbursement->gl_account_id }}">
                                        <p class="mt-1 text-xs text-gray-500">Credit account cannot be edited at this step
                                        </p>
                                    @endif
                                    @error('credit_account_id')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-900 mb-1">
                            Remarks <span id="remarks-required" class="text-red-500 hidden">*</span>
                        </label>
                        <textarea name="remarks" id="remarks" rows="2" placeholder="Optional notes for this approval"
                            class="w-full rounded-lg border border-gray-300 p-2.5 text-xs"></textarea>
                        <p id="remarks-error" class="mt-1 text-xs text-red-600 hidden">Remarks are required for rejection
                            or return.</p>
                    </div>
                </form>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 px-8 py-5 border-t border-gray-200 bg-gray-50">
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
                <button type="submit" form="approve-form"
                    class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ $step->is_final_approval ? 'Approve & Release for Payment' : 'Approve & Forward' }}
                </button>
            </div>
        </div>
    </div>

    <script>
        function reimbursementApproval() {
            return {
                lines: [],

                init() {
                    this.lines = @js($reimbursement->details->map(fn($d) => ['amount' => (float) $d->amount])->values());
                },

                recalcTotal() {
                    this.lines = [...this.lines];
                },

                get total() {
                    return this.lines.reduce((sum, l) => sum + (Number(l.amount) || 0), 0);
                },

                formatNumber(value) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }).format(value || 0);
                }
            };
        }

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
                form.action = "{{ route('cm.reimbursement.return', $transaction->id) }}";
            } else if (action === 'reject') {
                form.action = "{{ route('cm.reimbursement.reject', $transaction->id) }}";
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
