@extends('dashboard')

@section('title', 'Review Journal Entry')

@section('content')
    <div class="mx-auto max-w-5xl" x-data="journalApproval()">
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
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            {{ $journal->journal_no }}
                        </h1>
                        <p class="mt-1 text-sm text-blue-100">
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

            <!-- Journal Information -->
            <div class="px-8 py-6 border-b border-gray-200 grid grid-cols-2 gap-3 md:grid-cols-4 text-xs">
                <div>
                    <div class="text-gray-500">Journal No</div>
                    <div class="font-medium text-gray-800">{{ $journal->journal_no }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Date</div>
                    <div class="font-medium text-gray-800">{{ $journal->journal_date->format('M d, Y') }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Type</div>
                    <div class="font-medium text-gray-800 capitalize">{{ $journal->journal_type }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Created By</div>
                    <div class="font-medium text-gray-800">
                        {{ $journal->creator?->last_name . ', ' . $journal->creator?->first_name ?? '—' }}</div>
                </div>
                <div class="col-span-2 md:col-span-4">
                    <div class="text-gray-500">Description</div>
                    <div class="font-medium text-gray-800">{{ $journal->description ?? '—' }}</div>
                </div>
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

            <!-- Journal Lines -->
            <div class="px-8 py-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Journal Lines</h2>

                <form method="POST" action="{{ route('gl.journals.approve', $transaction->id) }}" id="approve-form">
                    @csrf
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">#</th>
                                    <th class="px-3 py-2 text-left">GL Account</th>
                                    <th class="px-3 py-2 text-left">Description</th>
                                    <th class="px-3 py-2 text-right">Debit</th>
                                    <th class="px-3 py-2 text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($journal->lines as $line)
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-3 py-2 text-gray-400">{{ $line->line_no }}</td>
                                        <td class="px-3 py-2">
                                            @if ($step->can_edit_chart_of_account)
                                                <select name="lines[{{ $line->id }}][gl_account_id]"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                                    <option value="">Select account&hellip;</option>
                                                    @foreach ($glAccounts as $account)
                                                        <option value="{{ $account->id }}" @selected($line->gl_account_id == $account->id)>
                                                            {{ $account->account_code }} — {{ $account->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="text-gray-700">
                                                    {{ $line->glAccount->account_code ?? '—' }}
                                                    {{ $line->glAccount ? '— ' . $line->glAccount->account_name : '' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            @if ($step->can_edit_chart_of_account || $step->can_edit_amount)
                                                <input type="text" name="lines[{{ $line->id }}][description]"
                                                    value="{{ $line->description }}"
                                                    class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                            @else
                                                <span class="text-gray-700">{{ $line->description ?? '—' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            @if ($step->can_edit_amount)
                                                <input type="number" step="0.01" min="0"
                                                    name="lines[{{ $line->id }}][debit]" value="{{ $line->debit }}"
                                                    class="w-28 rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                <span
                                                    class="text-gray-700">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            @if ($step->can_edit_amount)
                                                <input type="number" step="0.01" min="0"
                                                    name="lines[{{ $line->id }}][credit]" value="{{ $line->credit }}"
                                                    class="w-28 rounded border border-gray-300 p-1.5 text-xs text-right">
                                            @else
                                                <span
                                                    class="text-gray-700">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 font-medium text-gray-700">
                                <tr>
                                    <td colspan="3" class="px-3 py-2 text-right">Total</td>
                                    <td class="px-3 py-2 text-right tabular-nums font-bold"
                                        x-text="formatNumber(totalDebit)">
                                        {{ number_format($journal->total_debit, 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums font-bold"
                                        x-text="formatNumber(totalCredit)">
                                        {{ number_format($journal->total_credit, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-900 mb-1">
                            Remarks <span id="remarks-required" class="text-red-500 hidden">*</span>
                        </label>
                        <textarea name="remarks" id="remarks" rows="2" placeholder="Optional notes for this approval"
                            class="w-full rounded-lg border border-gray-300 p-2.5 text-xs"></textarea>
                        <p id="remarks-error" class="mt-1 text-xs text-red-600 hidden">Remarks are required for rejection or
                            return.</p>
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
                    class="rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">
                    {{ $step->is_final_approval ? 'Approve & Post' : 'Approve & Forward' }}
                </button>
            </div>
        </div>
    </div>

    <script>
        function journalApproval() {
            return {
                get totalDebit() {
                    const form = document.getElementById('approve-form');
                    const debitInputs = form.querySelectorAll('input[name*="[debit]"]');
                    let total = 0;
                    debitInputs.forEach(input => {
                        total += parseFloat(input.value) || 0;
                    });
                    return total;
                },
                get totalCredit() {
                    const form = document.getElementById('approve-form');
                    const creditInputs = form.querySelectorAll('input[name*="[credit]"]');
                    let total = 0;
                    creditInputs.forEach(input => {
                        total += parseFloat(input.value) || 0;
                    });
                    return total;
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

            if (!remarks.value.trim()) {
                remarksRequired.classList.remove('hidden');
                remarksError.classList.remove('hidden');
                remarks.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                remarks.focus();
                return;
            }

            remarksRequired.classList.add('hidden');
            remarksError.classList.add('hidden');
            remarks.classList.remove('border-red-500', 'ring-1', 'ring-red-500');

            if (action === 'return') {
                form.action = "{{ route('gl.journals.return', $transaction->id) }}";
            } else if (action === 'reject') {
                form.action = "{{ route('gl.journals.reject', $transaction->id) }}";
            }

            form.submit();
        }

        document.getElementById('remarks')?.addEventListener('input', function() {
            if (this.value.trim()) {
                document.getElementById('remarks-required').classList.add('hidden');
                document.getElementById('remarks-error').classList.add('hidden');
                this.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
            }
        });
    </script>
@endsection
