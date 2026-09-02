@extends('dashboard')

@section('title', 'Liquidate Cash Advance')

@section('content')
    <div class="mx-auto max-w-6xl" x-data="liquidationBuilder()">
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

        <div class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-8 py-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">Liquidate Cash Advance</h1>
                        <p class="mt-1 text-sm text-green-100">
                            CA-{{ str_pad($cashAdvance->id, 6, '0', STR_PAD_LEFT) }} — Disbursed:
                            {{ number_format($cashAdvance->disbursed_amount, 2) }}
                        </p>
                    </div>
                    <a href="{{ route('cm.ca') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20">
                        Back
                    </a>
                </div>
            </div>

            @if (($pendingLiquidationAmount ?? 0) > 0)
                <div class="mx-8 mt-4 rounded-lg border border-yellow-300 bg-yellow-50 p-3 text-sm text-yellow-800">
                    <strong>Heads up:</strong> {{ number_format($pendingLiquidationAmount, 2) }} from another liquidation on
                    this Cash Advance is already pending approval. The available balance below already excludes it.
                </div>
            @endif

            <!-- CA Summary -->
            <div class="px-8 py-4 border-b border-gray-200 grid grid-cols-2 gap-3 md:grid-cols-5 text-sm">
                <div>
                    <span class="text-gray-500">Disbursed Amount</span>
                    <div class="font-semibold text-gray-900">{{ number_format($cashAdvance->disbursed_amount, 2) }}</div>
                </div>
                <div>
                    <span class="text-gray-500">Already Liquidated</span>
                    <div class="font-semibold text-gray-900">{{ number_format($cashAdvance->liquidated_amount, 2) }}</div>
                </div>
                <div>
                    <span class="text-gray-500">Pending Liquidations</span>
                    <div class="font-semibold text-gray-900">{{ number_format($pendingLiquidationAmount ?? 0, 2) }}</div>
                </div>
                <div>
                    <span class="text-gray-500">Available Balance</span>
                    <div class="font-semibold text-green-600">{{ number_format($remainingAmount ?? 0, 2) }}</div>
                </div>
                <div>
                    <span class="text-gray-500">Purpose</span>
                    <div class="font-semibold text-gray-900 truncate">{{ $cashAdvance->purpose }}</div>
                </div>
            </div>

            @php
                $isEdit = $liquidation->exists ?? false;
                $formAction = $isEdit
                    ? route('cm.liquidation.update', $liquidation)
                    : route('cm.liquidation.store', $cashAdvance);
            @endphp

            <form method="POST" action="{{ $formAction }}" class="px-8 py-6" x-ref="liquidationForm">
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

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Liquidation Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="liquidation_date" value="{{ old('liquidation_date', date('Y-m-d')) }}"
                            required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <input type="text" name="remarks" value="{{ old('remarks', $liquidation->remarks ?? '') }}"
                            placeholder="Additional notes for this liquidation"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                </div>

                <!-- Line Items (Debit Accounts) -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Expense Details (Debit Accounts)</h3>
                        <button type="button" @click="addDetail()"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                            + Add Expense
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">#</th>
                                    <th class="px-3 py-2 text-left">Expense Date</th>
                                    <th class="px-3 py-2 text-left">Description</th>
                                    <th class="px-3 py-2 text-left">Debit Account</th>
                                    <th class="px-3 py-2 text-right">Amount</th>
                                    <th class="px-3 py-2 text-left">Reference</th>
                                    <th class="px-3 py-2 text-center w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(detail, index) in details" :key="detail._key">
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-3 py-2 text-gray-400" x-text="index + 1"></td>
                                        <td class="px-3 py-2">
                                            <input type="date" :name="`details[${index}][expense_date]`"
                                                x-model="detail.expense_date" required
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`details[${index}][description]`"
                                                x-model="detail.description" required placeholder="Expense description"
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <select :name="`details[${index}][gl_account_id]`"
                                                x-model="detail.gl_account_id" required
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                                <option value="">Select account&hellip;</option>
                                                @foreach ($glAccounts as $account)
                                                    <option value="{{ $account->id }}">
                                                        {{ $account->account_code }} &mdash; {{ $account->account_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0.01"
                                                :name="`details[${index}][amount]`" x-model.number="detail.amount"
                                                @input="recalcTotal()" required
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs text-right focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`details[${index}][reference]`"
                                                x-model="detail.reference" maxlength="50"
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" x-show="details.length > 1"
                                                @click.prevent="removeDetail(index)"
                                                class="text-red-500 hover:text-red-700">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 font-semibold text-gray-700">
                                <tr>
                                    <td colspan="4" class="px-3 py-2 text-right text-sm">Total Expenses</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sm"
                                        x-text="formatNumber(totalExpenses)"></td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr class="border-t border-gray-200">
                                    <td colspan="4" class="px-3 py-2 text-right text-sm">
                                        Available Balance
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sm font-bold text-green-600"
                                        x-text="formatNumber(remainingBalance)"></td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr x-show="excessAmount > 0" class="bg-indigo-50">
                                    <td colspan="4" class="px-3 py-2 text-right text-sm text-indigo-700">
                                        ⚠️ Excess Amount (will create Reimbursement Payable)
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sm font-bold text-indigo-700"
                                        x-text="formatNumber(excessAmount)"></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-2 text-xs text-gray-500">
                        Total expenses cannot exceed the remaining CA balance of {{ number_format($remainingAmount, 2) }}
                    </div>
                </div>

                <!-- Credit Account Selection (Reimbursement Payable) - Only shown when excess exists -->
                <div x-show="excessAmount > 0" x-cloak class="mb-4 p-4 border border-indigo-200 bg-indigo-50 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-1">
                            <svg class="h-5 w-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-indigo-800">Excess Amount Detected</h4>
                            <p class="text-xs text-indigo-600">
                                Total expenses exceed the available balance by <strong
                                    x-text="formatNumber(excessAmount)"></strong>.
                                This excess will be credited to the selected Reimbursement Payable account.
                            </p>
                            <div class="mt-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Credit Account (Reimbursement Payable) <span class="text-red-500">*</span>
                                </label>
                                <select name="credit_account_id" :required="excessAmount > 0"
                                    class="w-full rounded-lg border border-indigo-300 bg-white px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <option value="">— Select Reimbursement Payable Account —</option>
                                    @foreach ($creditAccounts as $account)
                                        <option value="{{ $account->id }}" @selected(old('credit_account_id', $creditAccountId ?? '') == $account->id)>
                                            {{ $account->account_code }} — {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">This account will be credited for the excess amount
                                </p>
                                @error('credit_account_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden field for credit account when no excess -->
                <input type="hidden" name="credit_account_id_hidden" x-bind:value="excessAmount > 0 ? '' : ''">

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5">
                    <a href="{{ route('cm.ca') }}"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" data-submit="draft"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Save Draft
                    </button>
                    <button type="submit" data-submit="approve"
                        class="rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">
                        Submit Liquidation for Approval
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function liquidationBuilder() {
            return {
                details: [],
                remainingBalance: {{ $remainingAmount }},
                isEdit: {{ $isEdit ? 'true' : 'false' }},

                init() {
                    @if ($isEdit && isset($lines) && count($lines) > 0)
                        this.details = @json($lines);
                    @else
                        this.addDetail();
                    @endif
                },

                addDetail() {
                    this.details.push({
                        _key: crypto.randomUUID(),
                        expense_date: '',
                        description: '',
                        gl_account_id: '',
                        amount: 0,
                        reference: '',
                    });
                },

                removeDetail(index) {
                    this.details.splice(index, 1);
                },

                recalcTotal() {
                    this.details = [...this.details];
                },

                get totalExpenses() {
                    return this.details.reduce((sum, d) => sum + (parseFloat(d.amount) || 0), 0);
                },

                get excessAmount() {
                    return Math.max(0, this.totalExpenses - this.remainingBalance);
                },

                formatNumber(value) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(value);
                }
            };
        }
    </script>
@endsection
