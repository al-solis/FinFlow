@extends('dashboard')

@section('title', 'Review Liquidation')

@section('content')
    <div class="mx-auto max-w-5xl" x-data="liquidationApproval()">

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
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            LIQ-{{ str_pad($liquidation->id, 6, '0', STR_PAD_LEFT) }}
                        </h1>
                        <p class="mt-1 text-sm text-green-100">
                            Against CA-{{ str_pad($liquidation->cash_advance_id, 6, '0', STR_PAD_LEFT) }} —
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

            <!-- Employee / CA context -->
            <div class="px-8 py-6 border-b border-gray-200 grid grid-cols-2 gap-3 md:grid-cols-5 text-xs">
                <div>
                    <div class="text-gray-500">Employee</div>
                    <div class="font-medium text-gray-800">
                        {{ $liquidation->employee?->last_name ?? '—' }}, {{ $liquidation->employee?->first_name ?? '' }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">Liquidation Date</div>
                    <div class="font-medium text-gray-800">
                        {{ $liquidation->liquidation_date?->format('M d, Y') ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">CA Disbursed Amount</div>
                    <div class="font-medium text-gray-800">
                        {{ number_format($liquidation->cashAdvance->disbursed_amount, 2) }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">Available Balance</div>
                    <div class="font-medium text-gray-800">{{ number_format($availableBalance, 2) }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Excess Amount</div>
                    <div class="font-medium {{ $excessAmount > 0 ? 'text-indigo-600' : 'text-gray-400' }}">
                        {{ $excessAmount > 0 ? number_format($excessAmount, 2) : '—' }}
                    </div>
                </div>
                @if ($pendingElsewhere > 0)
                    <div class="col-span-2 md:col-span-5">
                        <div class="rounded-lg border border-yellow-300 bg-yellow-50 px-3 py-2 text-yellow-800">
                            {{ number_format($pendingElsewhere, 2) }} from another liquidation on this Cash Advance is
                            also pending approval — the Available Balance above already excludes it.
                        </div>
                    </div>
                @endif
                @if ($excessAmount > 0)
                    <div class="col-span-2 md:col-span-5">
                        <div class="rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-indigo-800">
                            <strong>Excess Amount:</strong> {{ number_format($excessAmount, 2) }}
                            will be credited to the selected Reimbursement Payable account.
                        </div>
                    </div>
                @endif
                @if ($liquidation->remarks)
                    <div class="col-span-2 md:col-span-5">
                        <div class="text-gray-500">Remarks</div>
                        <div class="font-medium text-gray-800">{{ $liquidation->remarks }}</div>
                    </div>
                @endif
            </div>

            <!-- Permission banner -->
            <div class="px-8 pt-4 flex flex-wrap gap-2 text-xs">
                @if ($step->can_edit_chart_of_account)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit GL accounts</span>
                @endif
                @if ($step->can_edit_amount)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit amounts</span>
                @endif
                @if (!$step->can_edit_chart_of_account && !$step->can_edit_amount)
                    <span class="rounded-full bg-gray-50 px-2.5 py-1 text-gray-500">View only — no line edits at this
                        step</span>
                @endif
            </div>

            <!-- Line items -->
            <div class="px-8 py-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Expense Details</h2>

                <form method="POST" action="{{ route('cm.liquidation.approve', $transaction->id) }}" id="approve-form">
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
                                @foreach ($liquidation->details as $detail)
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
                                                    @foreach ($glAccounts as $account)
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
                                    <td class="px-3 py-2 text-right tabular-nums text-sm" x-text="formatNumber(total)"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Credit Account Selection (Reimbursement Payable) - Only editable if step allows -->
                    @if ($excessAmount > 0)
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
                                        Credit account for the excess amount: <strong
                                            x-text="formatNumber(excessAmount)"></strong>
                                    </p>
                                    <div class="mt-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Credit Account (Reimbursement Payable) <span class="text-red-500">*</span>
                                        </label>
                                        @if ($step->can_edit_chart_of_account)
                                            <select name="credit_account_id" :required="excessAmount > 0"
                                                class="w-full rounded-lg border border-indigo-300 bg-white px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                <option value="">— Select Reimbursement Payable Account —</option>
                                                @foreach ($creditAccounts as $account)
                                                    <option value="{{ $account->id }}" @selected(old('credit_account_id', $liquidation->credit_account_id) == $account->id)>
                                                        {{ $account->account_code }} — {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1 text-xs text-indigo-600">You have permission to edit the credit
                                                account</p>
                                        @else
                                            <div class="font-medium text-gray-800 py-2.5">
                                                {{ $liquidation->creditAccount?->account_code ?? '—' }}
                                                {{ $liquidation->creditAccount ? '— ' . $liquidation->creditAccount->account_name : '' }}
                                            </div>
                                            <input type="hidden" name="credit_account_id"
                                                value="{{ $liquidation->credit_account_id }}">
                                            <p class="mt-1 text-xs text-gray-500">Credit account cannot be edited at this
                                                step</p>
                                        @endif
                                        @error('credit_account_id')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Attachments -->
                    <div class="px-8 py-6 border-b border-gray-200">
                        <h2 class="text-sm font-semibold text-gray-700 mb-4">Attachments</h2>
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
                                                <svg class="h-6 w-6 {{ $icon }}" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
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
                                        <a href="{{ route('cm.liquidation.download-attachment', $attachment->id) }}"
                                            target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                            Download
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500">No attachments uploaded.</p>
                        @endif
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-900 mb-1">Remarks</label>
                        <textarea name="remarks" rows="2" placeholder="Optional notes for this approval"
                            class="w-full rounded-lg border border-gray-300 p-2.5 text-xs"></textarea>
                    </div>
                </form>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 px-8 py-5 border-t border-gray-200 bg-gray-50">
                @if ($step->can_return_to_requester)
                    <button type="button" @click="submitAs('{{ route('cm.liquidation.return', $transaction->id) }}')"
                        class="rounded-lg border border-orange-300 bg-white px-5 py-2.5 text-sm font-medium text-orange-700 hover:bg-orange-50">
                        Return to Requester
                    </button>
                @endif
                <button type="button" @click="submitAs('{{ route('cm.liquidation.reject', $transaction->id) }}')"
                    class="rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50">
                    Reject
                </button>
                <button type="submit" form="approve-form"
                    class="rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">
                    {{ $step->is_final_approval ? 'Approve & Clear Advance' : 'Approve & Forward' }}
                </button>
            </div>
        </div>
    </div>

    <script>
        function liquidationApproval() {
            return {
                lines: [],
                excessAmount: {{ $excessAmount }},

                init() {
                    this.lines = @js($liquidation->details->map(fn($d) => ['amount' => (float) $d->amount])->values());
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
                },

                submitAs(url) {
                    const form = document.getElementById('approve-form');
                    const remarks = form.querySelector('[name="remarks"]').value;
                    if (!remarks && (url.includes('/return') || url.includes('/reject'))) {
                        alert('Remarks are required to return or reject a request.');
                        return;
                    }
                    const original = form.action;
                    form.action = url;
                    form.submit();
                    form.action = original;
                },
            };
        }
    </script>
@endsection
