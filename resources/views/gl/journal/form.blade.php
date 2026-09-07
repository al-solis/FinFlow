@extends('dashboard')

@section('title', $isEdit ? 'Edit Journal Entry' : 'New Journal Entry')

@section('content')
    <div class="mx-auto max-w-6xl" x-data="journalBuilder()">
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
                    <span><strong>Warning:</strong> No approver has been configured for Journal Entries. You can save a
                        draft, but you will not be able to submit for approval until an approver is set up.</span>
                </div>
            </div>
        @endif

        @php
            $formAction = $isEdit ? route('gl.journals.update', $journal) : route('gl.journals.store');
        @endphp

        <form method="POST" action="{{ $formAction }}"
            class="mt-5 mb-5 bg-white rounded-xl shadow-sm border border-gray-200">
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

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            {{ $isEdit ? 'Edit Journal Entry' : 'New Journal Entry' }}
                        </h1>
                        <p class="mt-1 text-sm text-blue-100">
                            {{ $isEdit ? 'Update your journal entry' : 'Create a manual journal entry' }}
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('gl.journal') }}"
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
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Journal Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="journal_date"
                            value="{{ old('journal_date', $journal->journal_date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                            required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('journal_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Journal Type <span class="text-red-500">*</span>
                        </label>
                        <select name="journal_type" required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="manual" @selected(old('journal_type', $journal->journal_type) === 'manual')>Manual</option>
                            <option value="adjustment" @selected(old('journal_type', $journal->journal_type) === 'adjustment')>Adjustment</option>
                            <option value="reversal" @selected(old('journal_type', $journal->journal_type) === 'reversal')>Reversal</option>
                        </select>
                        @error('journal_type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>
                        <div class="py-2.5 text-sm font-medium text-gray-600">
                            <span
                                class="inline-flex rounded-full {{ $journal->statusBadgeClass() }} px-2.5 py-1 text-xs font-medium">
                                {{ $journal->statusLabel() }}
                            </span>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="description" value="{{ old('description', $journal->description) }}"
                            required placeholder="Brief description of the journal entry"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('description')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if ($isEdit)
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-xs text-gray-500">
                            <strong>Journal No:</strong> {{ $journal->journal_no }}
                            @if ($journal->submitted_at)
                                <br><strong>Submitted:</strong> {{ $journal->submitted_at->format('M d, Y g:i A') }}
                            @endif
                            @if ($journal->posted_at)
                                <br><strong>Posted:</strong> {{ $journal->posted_at->format('M d, Y g:i A') }}
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <!-- Journal Lines -->
            <div class="px-8 py-6 border-t border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700">Journal Lines</h2>
                    <span class="text-xs text-gray-500">Total must balance</span>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase">
                            <tr>
                                <th class="px-3 py-2 text-left">#</th>
                                <th class="px-3 py-2 text-left">GL Account</th>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-right">Debit</th>
                                <th class="px-3 py-2 text-right">Credit</th>
                                <th class="px-3 py-2 text-center w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(line, index) in lines" :key="line._key">
                                <tr class="border-t border-gray-100 align-top">
                                    <td class="px-3 py-2 text-gray-400" x-text="index + 1"></td>
                                    <td class="px-3 py-2">
                                        <select :name="`lines[${index}][gl_account_id]`" x-model="line.gl_account_id"
                                            required class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                            <option value="">Select account&hellip;</option>
                                            @foreach ($glAccounts as $account)
                                                <option value="{{ $account->id }}">
                                                    {{ $account->account_code }} — {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" :name="`lines[${index}][description]`"
                                            x-model="line.description" placeholder="Line description"
                                            class="w-full rounded border border-gray-300 p-1.5 text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" min="0" :name="`lines[${index}][debit]`"
                                            x-model.number="line.debit" @input="recalcTotals()"
                                            class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" min="0"
                                            :name="`lines[${index}][credit]`" x-model.number="line.credit"
                                            @input="recalcTotals()"
                                            class="w-full rounded border border-gray-300 p-1.5 text-xs text-right">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" x-show="lines.length > 2"
                                            @click.prevent="removeLine(index)"
                                            class="text-red-500 hover:text-red-700">&times;</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 font-medium text-gray-700">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right">Total</td>
                                <td class="px-3 py-2 text-right tabular-nums" x-text="formatNumber(totalDebit)"></td>
                                <td class="px-3 py-2 text-right tabular-nums" x-text="formatNumber(totalCredit)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="isBalanced" class="bg-green-50">
                                <td colspan="6" class="px-3 py-2 text-center text-xs text-green-700">
                                    Balanced — Debits equal Credits
                                </td>
                            </tr>
                            <tr x-show="!isBalanced" class="bg-red-50">
                                <td colspan="6" class="px-3 py-2 text-center text-xs text-red-700">
                                    Not Balanced — Difference: <span x-text="formatNumber(difference)"></span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" @click="addLine()"
                    class="mt-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    + Add Line
                </button>
            </div>
        </form>
    </div>

    <script>
        function journalBuilder() {
            return {
                lines: [],
                isBalanced: true,

                init() {
                    // Debug: Log what we're receiving
                    console.log('Lines from PHP:', @json($lines ?? []));

                    @if ($isEdit && isset($lines) && count($lines) > 0)
                        // Map the lines to ensure they have the correct structure
                        this.lines = @json($lines).map(line => ({
                            _key: crypto.randomUUID(),
                            id: line.id || null,
                            gl_account_id: line.gl_account_id || '',
                            description: line.description || '',
                            debit: parseFloat(line.debit) || 0,
                            credit: parseFloat(line.credit) || 0,
                        }));
                    @else
                        // Start with 2 empty lines
                        this.lines = [{
                                _key: crypto.randomUUID(),
                                gl_account_id: '',
                                description: '',
                                debit: 0,
                                credit: 0,
                            },
                            {
                                _key: crypto.randomUUID(),
                                gl_account_id: '',
                                description: '',
                                debit: 0,
                                credit: 0,
                            }
                        ];
                    @endif

                    // Ensure we have at least 2 lines
                    if (this.lines.length < 2) {
                        while (this.lines.length < 2) {
                            this.lines.push({
                                _key: crypto.randomUUID(),
                                gl_account_id: '',
                                description: '',
                                debit: 0,
                                credit: 0,
                            });
                        }
                    }

                    this.recalcTotals();
                },

                addLine() {
                    this.lines.push({
                        _key: crypto.randomUUID(),
                        gl_account_id: '',
                        description: '',
                        debit: 0,
                        credit: 0,
                    });
                },

                removeLine(index) {
                    if (this.lines.length > 2) {
                        this.lines.splice(index, 1);
                        this.recalcTotals();
                    }
                },

                recalcTotals() {
                    // Ensure only one of debit/credit per line
                    this.lines.forEach(line => {
                        if (line.debit > 0 && line.credit > 0) {
                            // If both are entered, prioritize credit and clear debit
                            line.debit = 0;
                        }
                    });
                },

                get totalDebit() {
                    return this.lines.reduce((sum, l) => sum + (parseFloat(l.debit) || 0), 0);
                },

                get totalCredit() {
                    return this.lines.reduce((sum, l) => sum + (parseFloat(l.credit) || 0), 0);
                },

                get difference() {
                    return Math.abs(this.totalDebit - this.totalCredit);
                },

                get isBalanced() {
                    return this.difference < 0.01;
                },

                formatNumber(value) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }).format(value || 0);
                }
            };
        }
    </script>
@endsection
