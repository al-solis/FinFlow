@extends('dashboard')

@section('title', $collection->exists ? 'Edit Collection' : 'New Collection')

@section('content')
    @php
        $isEdit = $collection->exists;
        $formAction = $isEdit ? route('ar.collections.update', $collection->id) : route('ar.collections.store');
        $input = 'mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-xs';
        $cell = 'w-full rounded border border-gray-300 p-1.5 text-xs';
        $label = 'block text-xs font-medium text-gray-900';
        $oldApps = old('applications');
        $apps = is_array($oldApps) ? array_values($oldApps) : $initialApplications;
        $dateOf = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '';
    @endphp

    <div class="mx-auto max-w-7xl">
        @if ($errors->any())
            <div id="alert-message" class="mt-5 mb-5 rounded-lg border border-red-300 bg-red-50 p-4 shadow-sm">
                <div class="flex items-start">
                    <svg class="h-5 w-5 mr-2 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <div class="font-semibold text-red-700">
                            Please correct the following errors:
                        </div>
                        <ul class="mt-2 list-disc list-inside text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if (!$hasWorkflow)
            <div class="mt-3 mb-3 rounded-lg border border-orange-300 bg-orange-50 p-4 text-sm text-orange-800">
                <strong>Warning:</strong> No approval workflow is configured for Collections. You can save a draft,
                but you cannot submit for approval until one is set up in the Workflow module.
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" x-data="collectionForm({
            customers: @js($customers),
            currencyId: @js((string) old('currency_id', $collection->currency_id ?? '')),
            paymentTermId: @js((string) old('payment_term_id', $collection->payment_term_id ?? '')),
            paymentMethodId: @js((string) old('payment_method_id', $collection->payment_method_id ?? '')),
            openInvoices: @js($openInvoices),
            applications: @js($apps),
            customerId: @js((string) old('customer_id', $collection->customer_id ?? $preselectedCustomerId)),
            amountReceived: @js((float) old('amount_received', $collection->amount_received ?? 0)),
            cwtAmount: @js((float) old('cwt_amount', $collection->cwt_amount ?? 0))
        })">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="mt-5 mb-5 bg-white rounded-2xl shadow-lg border border-gray-200/80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">
                                {{ $isEdit ? 'Edit Collection' : 'New Collection' }}
                            </h1>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ $isEdit ? $collection->collection_no : 'Saved as a draft without a number. The collection number is issued when you submit for approval.' }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ $isEdit ? route('ar.collections.show', $collection) : route('ar.col') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-medium text-white hover:bg-white/20 border border-white/20">
                                Back
                            </a>
                            <button type="submit" name="action" value="draft"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 hover:bg-white">
                                Save Draft
                            </button>
                            <button type="submit" name="action" value="submit" @disabled(!$hasWorkflow)
                                title="{{ $hasWorkflow ? '' : 'An approval workflow must be configured first' }}"
                                onclick="return confirm('Submit for approval? A collection number will be issued.');"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-50 shadow-lg disabled:cursor-not-allowed disabled:opacity-50">
                                Submit for Approval
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    {{-- Header --}}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="md:col-span-2">
                            <label class="{{ $label }}">Customer <span class="text-red-500">*</span></label>
                            <select name="customer_id" required x-model="customerId" @change="onCustomerChange()"
                                class="{{ $input }}">
                                <option value="">— Select Customer —</option>
                                <template x-for="c in customers" :key="c.id">
                                    <option :value="c.id" :selected="String(c.id) === String(customerId)"
                                        x-text="c.code + ' — ' + c.name">
                                    </option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Collection Date <span class="text-red-500">*</span></label>
                            <input type="date" name="collection_date" required
                                value="{{ old('collection_date', $dateOf($collection->collection_date ?? today())) }}"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Currency</label>
                            <select name="currency_id" x-model="currencyId" class="{{ $input }}">
                                <option value="">— Default —</option>
                                @foreach ($currencies as $cur)
                                    <option value="{{ $cur->id }}">
                                        {{ $cur->code }} — {{ $cur->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="{{ $label }}">Payment Method</label>
                            <select name="payment_method_id" x-model="paymentMethodId" class="{{ $input }}">
                                <option value="">— Select —</option>
                                @foreach ($paymentMethods as $m)
                                    <option value="{{ $m->id }}">
                                        {{ $m->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Deposit To <span class="text-red-500">*</span></label>
                            <select name="bank_account_id" required class="{{ $input }}">
                                <option value="">— Select Bank/Cash Account —</option>
                                @foreach ($bankAccounts as $b)
                                    <option value="{{ $b->id }}" @selected(old('bank_account_id', $collection->bank_account_id) == $b->id)>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">The bank account's linked GL account receives
                                the cash.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Reference No.</label>
                            <input type="text" name="reference_number" maxlength="100"
                                value="{{ old('reference_number', $collection->reference_number) }}"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Exchange Rate</label>
                            <input type="number" step="0.000001" min="0.000001" name="exchange_rate"
                                value="{{ old('exchange_rate', $collection->exchange_rate ?? 1) }}"
                                class="{{ $input }} text-right">
                        </div>

                        <div>
                            <label class="{{ $label }}">Check No.</label>
                            <input type="text" name="check_number" maxlength="50"
                                value="{{ old('check_number', $collection->check_number) }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Check Bank</label>
                            <input type="text" name="check_bank" maxlength="100"
                                value="{{ old('check_bank', $collection->check_bank) }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Check Date</label>
                            <input type="date" name="check_date"
                                value="{{ old('check_date', $dateOf($collection->check_date)) }}"
                                class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Amount Received <span
                                    class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount_received" required
                                x-model.number="amountReceived" class="{{ $input }} text-right">
                        </div>

                        <div>
                            <label class="{{ $label }}">CWT Tax</label>
                            <select name="cwt_tax_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($cwtTaxes as $t)
                                    <option value="{{ $t->id }}" @selected(old('cwt_tax_id', $collection->cwt_tax_id) == $t->id)>
                                        {{ $t->code }} — {{ $t->name }}
                                        ({{ number_format((float) $t->rate, 2) }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">CWT Amount</label>
                            <input type="number" step="0.01" min="0" name="cwt_amount"
                                x-model.number="cwtAmount" class="{{ $input }} text-right">
                        </div>
                        <div>
                            <label class="{{ $label }}">Advance/Deposit Account</label>
                            <select name="advance_account_id" class="{{ $input }}">
                                <option value="">— None —</option>
                                @foreach ($advanceAccounts as $a)
                                    <option value="{{ $a->id }}" @selected(old('advance_account_id', $collection->advance_account_id) == $a->id)>
                                        {{ $a->account_code }} — {{ $a->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">Required when unapplied cash remains.</p>
                        </div>
                        <div class="md:col-span-3">
                            <label class="{{ $label }}">Remarks</label>
                            <input type="text" name="remarks" maxlength="2000"
                                value="{{ old('remarks', $collection->remarks) }}" class="{{ $input }}">
                        </div>
                    </div>

                    {{-- Applications --}}
                    <div class="mt-6 mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Apply to Invoices</h3>
                        <button type="button" @click="addApplication()"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                            + Add Invoice
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-[900px] w-full text-xs">
                            <thead class="bg-gray-50 uppercase text-gray-500">
                                <tr>
                                    <th class="px-2 py-2 text-left w-6">#</th>
                                    <th class="px-2 py-2 text-left">Invoice</th>
                                    <th class="px-2 py-2 text-right w-28">Invoice Balance</th>
                                    <th class="px-2 py-2 text-right w-32">Amount Applied</th>
                                    <th class="px-2 py-2 text-right w-28">CWT Applied</th>
                                    <th class="px-2 py-2 text-right w-28">Remaining on Invoice</th>
                                    <th class="px-2 py-2 w-6"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(app, index) in applications" :key="app._key">
                                    <tr class="border-t border-gray-100 align-top">
                                        <td class="px-2 py-2 text-gray-400" x-text="index + 1"></td>
                                        <td class="px-2 py-2">
                                            <select :name="`applications[${index}][sales_invoice_id]`"
                                                @change="app.sales_invoice_id = $event.target.value"
                                                class="{{ $cell }}">
                                                <option value="">— Select Invoice —</option>
                                                <template x-for="inv in openInvoicesForCurrentCustomer()"
                                                    :key="inv.id">
                                                    <option :value="inv.id"
                                                        x-text="inv.invoice_no + ' — ' + fmt(inv.amount_due) + ' due ' + (inv.due_date ? new Date(inv.due_date).toLocaleDateString('en-US', {month: '2-digit', day: '2-digit', year: 'numeric'}) : '')"
                                                        :selected="String(inv.id) === String(app.sales_invoice_id)">
                                                    </option>
                                                </template>
                                            </select>
                                            <input type="hidden" :name="`applications[${index}][invoice_due_display]`"
                                                value="">
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-gray-700"
                                            x-text="fmt(invoiceDue(app.sales_invoice_id, index))"></td>
                                        <td class="px-2 py-2 text-right">
                                            <input type="number" step="0.01" min="0"
                                                :name="`applications[${index}][amount_applied]`"
                                                x-model.number="app.amount_applied"
                                                class="{{ $cell }} text-right">
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            <input type="number" step="0.01" min="0"
                                                :name="`applications[${index}][cwt_applied]`"
                                                x-model.number="app.cwt_applied" class="{{ $cell }} text-right">
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-gray-700"
                                            x-text="fmt(invoiceDue(app.sales_invoice_id, index) - (Number(app.amount_applied) || 0) - (Number(app.cwt_applied) || 0))">
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" @click.prevent="removeApplication(index)"
                                                class="text-red-500 hover:text-red-700">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="applications.length === 0">
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                                        No applications yet. Click "+ Add Invoice" to apply this collection.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-[11px] text-gray-400">
                        Unapplied cash (Amount Received − Amount Applied) goes to the <strong>Advance/Deposit
                            Account</strong>.
                    </p>

                    {{-- Summary --}}
                    <div class="mt-4 flex justify-end">
                        <table class="w-full max-w-sm text-xs">
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="py-1.5 text-gray-500">Amount Received</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(amountReceived)"></td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500">Less: Applied to Invoices</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(totalApplied)"></td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-500">Less: CWT Applied</td>
                                    <td class="py-1.5 text-right tabular-nums" x-text="fmt(totalCwtApplied)"></td>
                                </tr>
                                <tr class="font-bold text-gray-900">
                                    <td class="py-2">Unapplied Cash</td>
                                    <td class="py-2 text-right tabular-nums" x-text="'₱' + fmt(unapplied)"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div x-show="cwtAmount > 0 && Math.abs(totalCwtApplied - cwtAmount) > 0.01"
                        class="mt-3 rounded border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-700">
                        CWT amounts must match: declared <span x-text="fmt(cwtAmount)"></span>,
                        applied <span x-text="fmt(totalCwtApplied)"></span>.
                    </div>

                    <div x-show="unapplied > 0.01 && !hasAdvanceAccount"
                        class="mt-3 rounded border border-orange-300 bg-orange-50 px-3 py-2 text-xs text-orange-800">
                        There is <strong x-text="fmt(unapplied)"></span></strong> of unapplied cash. Select an
                        <strong>Advance/Deposit Account</strong> before posting.
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        const makeKey = () =>
            (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') ?
            crypto.randomUUID() :
            'k_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2);

        function collectionForm(data) {
            const r2 = n => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

            return {
                ...data,

                init() {
                    this.applications = this.applications.map(a => ({
                        ...a,
                        _key: a._key || makeKey(),
                    }));
                    if (!this.applications.length) this.addApplication();
                },

                customer() {
                    return this.customers.find(c => String(c.id) === String(this.customerId)) || null;
                },

                openInvoicesForCurrentCustomer() {
                    return this.openInvoices.filter(inv => String(inv.customer_id) === String(this.customerId));
                },

                invoiceDue(invoiceId, excludeIndex = -1) {
                    const inv = this.openInvoices.find(i => String(i.id) === String(invoiceId));
                    if (!inv) return 0;
                    // Subtract amounts already applied to this invoice in this form
                    const alreadyApplied = this.applications
                        .filter((a, idx) => idx !== excludeIndex && String(a.sales_invoice_id) === String(invoiceId))
                        .reduce((s, a) => s + (Number(a.amount_applied) || 0) + (Number(a.cwt_applied) || 0), 0);
                    return Math.max(0, r2(Number(inv.amount_due) - alreadyApplied));
                },

                onCustomerChange() {
                    const c = this.customer();

                    // Load customer defaults
                    this.currencyId = String(c?.currency_id || '');
                    this.paymentMethodId = String(c?.payment_method_id || '');

                    // Clear applications when the customer changes
                    this.applications = [];
                    this.addApplication();
                },

                addApplication() {
                    this.applications.push({
                        _key: makeKey(),
                        sales_invoice_id: '',
                        amount_applied: 0,
                        cwt_applied: 0,
                    });
                },
                removeApplication(i) {
                    this.applications.splice(i, 1);
                },

                get totalApplied() {
                    return r2(this.applications.reduce((s, a) => s + (Number(a.amount_applied) || 0), 0));
                },
                get totalCwtApplied() {
                    return r2(this.applications.reduce((s, a) => s + (Number(a.cwt_applied) || 0), 0));
                },
                get unapplied() {
                    return r2(Number(this.amountReceived) - this.totalApplied);
                },

                hasAdvanceAccount: @js((bool) old('advance_account_id', $collection->advance_account_id ?? false)),

                fmt(n) {
                    return new Intl.NumberFormat('en-PH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(n || 0);
                },
            };
        }
    </script>
@endsection
