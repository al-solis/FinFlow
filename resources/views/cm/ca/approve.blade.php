{{-- resources/views/cm/ca/approve.blade.php --}}
@extends('dashboard')

@section('title', 'Review Cash Advance')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            CA-{{ str_pad($cashAdvance->id, 6, '0', STR_PAD_LEFT) }}
                        </h1>
                        <p class="mt-1 text-sm text-blue-100">
                            Step: {{ $step->step_name }}
                            @if ($step->is_final_approval)
                                <span class="ml-2 rounded bg-white/20 px-2 py-0.5 text-xs font-medium">Final Approval</span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('approvals.index') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20">
                        Back to Inbox
                    </a>
                </div>
            </div>

            <!-- Details -->
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-xs text-gray-500">Employee</div>
                        <div class="font-medium text-gray-800">
                            {{ $cashAdvance->employee?->last_name ?? '—' }},
                            {{ $cashAdvance->employee?->first_name ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Amount</div>
                        <div class="font-bold text-gray-900 text-lg">{{ number_format($cashAdvance->amount, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Expected Liquidation Date</div>
                        <div class="font-medium text-gray-800">
                            {{ $cashAdvance->expected_liquidation_date?->format('M d, Y') ?? '—' }}
                        </div>
                    </div>
                    <div class="col-span-2 md:col-span-3">
                        <div class="text-xs text-gray-500">Purpose</div>
                        <div class="font-medium text-gray-800">{{ $cashAdvance->purpose }}</div>
                    </div>
                    @if ($cashAdvance->submitted_at)
                        <div>
                            <div class="text-xs text-gray-500">Submitted</div>
                            <div class="font-medium text-gray-800">{{ $cashAdvance->submitted_at->format('M d, Y g:i A') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Approval Form -->
            <form method="POST" action="{{ route('cm.ca.approve', $transaction->id) }}" class="px-8">
                @csrf

                <!-- GL Account Section - Editable if step allows -->
                <div class="py-4 border-b border-gray-200">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                GL Debit Account <span class="text-red-500">*</span>
                            </label>
                            @if ($step->can_edit_chart_of_account)
                                <select name="gl_account_id" required
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    <option value="">— Select Account —</option>
                                    @foreach ($glAccounts as $account)
                                        <option value="{{ $account->id }}" @selected(old('gl_account_id', $cashAdvance->gl_account_id) == $account->id)>
                                            {{ $account->account_code }} — {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-blue-600">You have permission to edit the GL account</p>
                            @else
                                <div class="font-medium text-gray-800 py-2.5">
                                    {{ $cashAdvance->glAccount?->getFormattedAccountCodeAttribute() ?? '—' }}
                                    {{ $cashAdvance->glAccount ? '— ' . $cashAdvance->glAccount->account_name : '' }}
                                </div>
                                <input type="hidden" name="gl_account_id" value="{{ $cashAdvance->gl_account_id }}">
                                <p class="mt-1 text-xs text-gray-500">GL account cannot be edited at this step</p>
                            @endif
                            @error('gl_account_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <div class="font-medium text-gray-800 py-2.5">
                                <span
                                    class="inline-flex rounded-full {{ $cashAdvance->statusBadgeClass() }} px-2.5 py-1 text-xs font-medium">
                                    {{ $cashAdvance->statusLabel() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permission Banner -->
                <div class="px-8 pt-4 flex flex-wrap gap-2 text-xs">
                    @if ($step->can_edit_chart_of_account)
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit GL account</span>
                    @endif
                    @if ($step->can_edit_tax)
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can add/modify tax</span>
                    @endif
                    @if ($step->can_edit_amount)
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700">Can edit amounts</span>
                    @endif
                    @if (!$step->can_edit_chart_of_account && !$step->can_edit_tax && !$step->can_edit_amount)
                        <span class="rounded-full bg-gray-50 px-2.5 py-1 text-gray-500">View only — no edits at this
                            step</span>
                    @endif
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                    <textarea name="remarks" rows="2" placeholder="Optional notes for this approval"
                        class="w-full rounded-lg border border-gray-300 p-2.5 text-sm"></textarea>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5">
                    @if ($step->can_return_to_requester)
                        <button type="submit" formaction="{{ route('cm.ca.return', $transaction->id) }}"
                            class="rounded-lg border border-orange-300 bg-white px-5 py-2.5 text-sm font-medium text-orange-700 hover:bg-orange-50">
                            Return to Requester
                        </button>
                    @endif
                    <button type="submit" formaction="{{ route('cm.ca.reject', $transaction->id) }}"
                        class="rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50">
                        Reject
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        {{ $step->is_final_approval ? 'Approve & Release for Payment' : 'Approve & Forward' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
