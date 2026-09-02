@extends('dashboard')

@section('title', 'New Reimbursement Request')

@section('content')
    <div class="mx-auto max-w-6xl" x-data="reimbursementBuilder()">
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
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-8 py-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">New Reimbursement Request</h1>
                        <p class="mt-1 text-sm text-indigo-100">Request reimbursement for out-of-pocket business expenses
                        </p>
                    </div>
                    <a href="{{ route('cm.liq') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20">
                        Back
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('cm.reimbursement.store') }}" class="px-8 py-6">
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Total Amount <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                            <input type="number" step="0.01" min="0.01" name="amount" x-model.number="totalAmount"
                                required class="w-full rounded-lg border border-gray-300 pl-8 pr-4 py-2.5 text-sm">
                        </div>
                        @error('amount')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Purpose <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="purpose" value="{{ old('purpose') }}" required
                            placeholder="Brief description of the reimbursement"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
                        @error('purpose')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Expense Details -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Expense Details</h3>
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
                                    <th class="px-3 py-2 text-left">GL Account</th>
                                    <th class="px-3 py-2 text-right">Amount</th>
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
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="`details[${index}][description]`"
                                                x-model="detail.description" required placeholder="Expense description"
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                        </td>
                                        <td class="px-3 py-2">
                                            <select :name="`details[${index}][gl_account_id]`"
                                                x-model="detail.gl_account_id" required
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs">
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
                                                class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
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
                                    <td colspan="4" class="px-3 py-2 text-right text-sm">Total Details</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sm"
                                        x-text="formatNumber(detailsTotal)"></td>
                                    <td></td>
                                </tr>
                                <tr x-show="detailsTotal != totalAmount" class="bg-red-50">
                                    <td colspan="4" class="px-3 py-2 text-right text-sm text-red-700">
                                        ⚠️ Total does not match the reimbursement amount
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sm font-bold text-red-700"
                                        x-text="formatNumber(detailsTotal - totalAmount)"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5">
                    <a href="{{ route('cm.liq') }}"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                        Submit Reimbursement for Approval
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function reimbursementBuilder() {
            return {
                details: [],
                totalAmount: 0,

                init() {
                    this.addDetail();
                },

                addDetail() {
                    this.details.push({
                        _key: crypto.randomUUID(),
                        expense_date: '',
                        description: '',
                        gl_account_id: '',
                        amount: 0,
                    });
                },

                removeDetail(index) {
                    this.details.splice(index, 1);
                },

                recalcTotal() {
                    this.details = [...this.details];
                },

                get detailsTotal() {
                    return this.details.reduce((sum, d) => sum + (parseFloat(d.amount) || 0), 0);
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
