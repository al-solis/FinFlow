<?php

namespace App\Http\Controllers;

use RuntimeException;
use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Services\ApprovalWorkflowService;
use App\Services\AuthorizationService;
use App\Services\SystemSettings;
use App\Traits\MasksPii;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\approval_transaction;
use App\Models\approval_transaction_history;
use App\Models\approval_workflow;
use App\Models\chart_of_account;
use App\Models\currency;
use App\Models\customer;
use App\Models\item;
use App\Models\sales_invoice;
use App\Models\sales_invoice_line;
use App\Models\tax_master;
use App\Models\term;
use App\Models\unit_of_measure;
use App\Models\invoice_series;
use App\Models\ar_setting;

class InvoiceController extends Controller
{
    use MasksPii;

    public function __construct(protected ApprovalWorkflowService $workflow)
    {
    }

    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    protected function masker()
    {
        return $this->getPiiMasker()->setContext(Modules::AR, Modules::AR_INV);
    }

    // ==================== LIST / DETAIL ====================

    public function index(Request $request)
    {
        $authz = $this->authz();
        $authz->authorizeRead(Modules::AR, Modules::AR_INV);

        $masker = $this->masker();
        $orgId = SystemSettings::getOrganizationId();

        $invoices = sales_invoice::with('customer')->where('organization_id', $orgId)
            ->when($request->filled('searchname'), function ($q) use ($request, $masker) {
                $s = '%' . $request->searchname . '%';
                $q->where(function ($w) use ($s, $masker) {
                    $w->where('invoice_no', 'like', $s)->orWhere('customer_po_no', 'like', $s)
                        ->orWhereHas('customer', function ($c) use ($s, $masker) {
                            $c->where('code', 'like', $s);
                            if ($masker->hasFullAccess()) {
                                $c->orWhere('name', 'like', $s);
                            }
                        });
                });
            })
            ->when($request->filled('searchapproval'), fn($q) => $q->where('approval_status', $request->searchapproval))
            ->when($request->filled('searchpayment'), function ($q) use ($request) {
                match ($request->searchpayment) {
                    'overdue' => $q->open()->whereDate('due_date', '<', today()),
                    default => $q->where('approval_status', '2')->whereNull('voided_at')->where('payment_status', $request->searchpayment),
                };
            })
            ->when($request->filled('datefrom'), fn($q) => $q->whereDate('invoice_date', '>=', $request->datefrom))
            ->when($request->filled('dateto'), fn($q) => $q->whereDate('invoice_date', '<=', $request->dateto))
            ->orderByDesc('invoice_date')->orderByDesc('id')
            ->paginate(config('app.paginate'))
            ->withQueryString();

        $invoices->getCollection()->each(
            fn($i) => $i->setAttribute('customer_display', $masker->mask($i->customer->name ?? '', 'full_name'))
        );

        $base = sales_invoice::where('organization_id', $orgId);

        return view('ar.invoice.index', [
            'invoices' => $invoices,
            'draftCount' => (clone $base)->whereIn('approval_status', ['0', '4'])->count(),
            'pendingCount' => (clone $base)->where('approval_status', '1')->count(),
            'openAmount' => (clone $base)->open()->sum('amount_due'),
            'overdueAmount' => (clone $base)->open()->whereDate('due_date', '<', today())->sum('amount_due'),
            'canCreate' => $authz->canCreate(Modules::AR, Modules::AR_INV),
        ]);
    }

    public function show(sales_invoice $invoice)
    {
        $authz = $this->authz();
        $authz->authorizeRead(Modules::AR, Modules::AR_INV);
        $this->assertOwnOrganization($invoice);

        $masker = $this->masker();
        $invoice->load([
            'customer',
            'term',
            'currency',
            'series',
            'lines.taxes.tax',
            'lines.uom',
            'lines.glAccount',
            'journal.lines.glAccount',
            'applications.collection',
            'latestApprovalTransaction.histories.actor',
            'postedCreditMemos',
            'postedDebitMemos',
            'creditMemos' => fn($q) => $q->latest('credit_date'),
            'debitMemos' => fn($q) => $q->latest('debit_date'),
        ]);

        $c = $invoice->customer;
        $billTo = [
            'name' => $masker->mask($invoice->bill_to_name ?: ($c->legal_name ?: $c->name), 'full_name'),
            'tin' => $masker->mask($invoice->bill_to_tin ?: $c->tax_id, 'tax_id'),
            'address' => $masker->mask($invoice->bill_to_address ?: $this->customerAddress($c), 'address'),
        ];

        $tx = $invoice->latestApprovalTransaction;
        $returnRemark = ($invoice->approval_status === '4' && $tx) ? $tx->histories->where('action', 'returned')->last() : null;

        return view('ar.invoice.show', [
            'invoice' => $invoice,
            'billTo' => $billTo,
            'tx' => $tx,
            'returnRemark' => $returnRemark,
            'canEdit' => $authz->canUpdate(Modules::AR, Modules::AR_INV) && $invoice->isEditable(),
            'canDelete' => $authz->canDelete(Modules::AR, Modules::AR_INV) && $invoice->approval_status === '0' && !$invoice->gl_journal_id,
            'hasWorkflow' => $this->hasWorkflow($invoice->organization_id),
        ]);
    }

    // ==================== CREATE / EDIT ====================

    public function create()
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_INV);

        return view('ar.invoice.form', $this->formData(new sales_invoice([
            'invoice_date' => today(),
            'exchange_rate' => 1,
        ])));
    }

    public function store(Request $request)
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_INV);

        return $this->persist($request, null);
    }

    public function edit(sales_invoice $invoice)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_INV);
        $this->assertOwnOrganization($invoice);
        abort_unless($invoice->isEditable(), 403, 'Only draft or returned invoices can be edited.');

        return view('ar.invoice.form', $this->formData($invoice));
    }

    public function update(Request $request, sales_invoice $invoice)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_INV);
        $this->assertOwnOrganization($invoice);
        abort_unless($invoice->isEditable(), 403, 'Only draft or returned invoices can be edited.');

        return $this->persist($request, $invoice);
    }

    public function submit(sales_invoice $invoice)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_INV);
        $this->assertOwnOrganization($invoice);
        abort_unless($invoice->isEditable(), 403, 'Only draft or returned invoices can be submitted.');

        try {
            $this->submitForApproval($invoice);
        } catch (ValidationException $e) {
            return redirect()->route('ar.invoices.show', $invoice)->with('error', collect($e->errors())->flatten()->implode(' '));
        } catch (RuntimeException $e) {
            return redirect()->route('ar.invoices.show', $invoice)->with('error', $e->getMessage());
        }

        return redirect()->route('ar.invoices.show', $invoice)->with('success', 'Invoice submitted for approval.');
    }

    public function destroy(sales_invoice $invoice)
    {
        $this->authz()->authorizeDelete(Modules::AR, Modules::AR_INV);
        $this->assertOwnOrganization($invoice);

        if ($invoice->approval_status !== '0' || $invoice->gl_journal_id || $invoice->invoice_no) {
            return redirect()->route('ar.invoices.show', $invoice)
                ->with('error', 'Only drafts that were never submitted can be deleted.');
        }

        $invoice->delete();

        return redirect()->route('ar.inv')->with('success', 'Draft invoice deleted.');
    }

    protected function persist(Request $request, ?sales_invoice $invoice)
    {
        $orgId = SystemSettings::getOrganizationId();

        $data = $request->validate([
            'invoice_series_id' => 'nullable|exists:invoice_series,id',
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'term_id' => 'nullable|exists:terms,id',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|gt:0',
            'tax_option' => 'required|integer|in:0,1,2',
            'customer_po_no' => 'nullable|string|max:50',
            'billing_period_from' => 'nullable|date',
            'billing_period_to' => 'nullable|date|after_or_equal:billing_period_from',
            'remarks' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.description' => 'required|string|max:1000',
            'lines.*.gl_account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.quantity' => 'required|numeric|gt:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_amount' => 'nullable|numeric|min:0',
            'lines.*.vat' => 'required|string',
            'lines.*.uom_id' => 'required|exists:unit_of_measures,id',
        ], [
            'lines.required' => 'Add at least one line.',
            'lines.*.description.required' => 'Every line needs a description.',
            'lines.*.gl_account_id.required' => 'Every line needs a revenue account.',
        ]);

        $customer = customer::where('organization_id', $orgId)->where('is_active', true)->find($data['customer_id']);
        if (!$customer) {
            throw ValidationException::withMessages(['customer_id' => 'Select an active customer.']);
        }
        if ($customer->credit_hold) {
            throw ValidationException::withMessages(['customer_id' => "Customer {$customer->code} is on credit hold."]);
        }

        $invoiceDate = Carbon::parse($data['invoice_date']);
        $dueDate = $data['due_date'] ?? null;
        if (!$dueDate && !empty($data['term_id'])) {
            $dueDate = $invoiceDate->copy()->addDays((int) term::whereKey($data['term_id'])->value('days'))->toDateString();
        }

        $saved = DB::transaction(function () use ($data, $invoice, $customer, $orgId, $dueDate) {
            $attrs = [
                'customer_id' => $customer->id,
                'bill_to_name' => $customer->legal_name ?: $customer->name,
                'bill_to_tin' => $customer->tax_id,
                'bill_to_address' => mb_substr($this->customerAddress($customer), 0, 500),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $dueDate,
                'term_id' => $data['term_id'] ?? null,
                'currency_id' => $data['currency_id'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'tax_option' => (int) $data['tax_option'],
                'customer_po_no' => $data['customer_po_no'] ?? null,
                'billing_period_from' => $data['billing_period_from'] ?? null,
                'billing_period_to' => $data['billing_period_to'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ];

            if ($invoice) {
                $invoice->update($attrs);
            } else {
                $invoice = sales_invoice::create($attrs + [
                    'organization_id' => $orgId,
                    'status' => '0',
                    'approval_status' => '0',
                    'payment_status' => 'unpaid',
                    'invoice_series_id' => $this->defaultSeriesForDraft($orgId),
                    'created_by' => Auth::id(),
                ]);
            }

            // FIX: upsert by line_no instead of delete-then-recreate, so line IDs
            // (and therefore approver-edit history, printed-PDF snapshots, etc.)
            // survive edits. Any line_no not present in the payload is removed.
            $existing = $invoice->lines()->get()->keyBy('line_no');
            $seen = [];

            foreach (array_values($data['lines']) as $i => $line) {
                $lineNo = $i + 1;
                $seen[] = $lineNo;
                $this->writeLine($invoice, $existing->get($lineNo), $lineNo, $line);
            }

            $invoice->lines()->whereNotIn('line_no', $seen)->delete();
            $invoice->recalcTotals();

            return $invoice;
        });

        if ($request->input('action') === 'submit') {
            try {
                $this->submitForApproval($saved);
            } catch (ValidationException $e) {
                return redirect()->route('ar.invoices.edit', $saved)->withInput()->withErrors($e->errors())
                    ->with('success', 'Saved as draft, but it could not be submitted.');
            } catch (RuntimeException $e) {
                return redirect()->route('ar.invoices.show', $saved)
                    ->with('error', 'Saved as draft, but it could not be submitted: ' . $e->getMessage());
            }

            return redirect()->route('ar.invoices.show', $saved)->with('success', 'Invoice submitted for approval.');
        }

        return redirect()->route('ar.invoices.show', $saved)->with('success', 'Invoice saved as draft.');
    }

    protected function defaultSeriesForDraft(int $orgId): ?int
    {
        return invoice_series::where('organization_id', $orgId)
            ->where('document_type', 'sales_invoice')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');
    }

    protected function submitForApproval(sales_invoice $invoice): void
    {

        $locked = DB::transaction(function () use ($invoice) {
            /** @var sales_invoice $locked */
            $locked = sales_invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->submitted_at && $locked->status !== '0') {
                throw ValidationException::withMessages(['approval' => 'This invoice has already been submitted.']);
            }
            if (!$locked->isEditable()) {
                throw ValidationException::withMessages(['approval' => 'This invoice can no longer be submitted.']);
            }

            $locked->load('customer', 'lines');
            if ($locked->lines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => 'Add at least one line.']);
            }
            if ((float) $locked->total_amount <= 0) {
                throw ValidationException::withMessages(['lines' => 'The invoice total must be greater than zero.']);
            }
            if (!$locked->customer->is_active || $locked->customer->credit_hold) {
                throw ValidationException::withMessages(['customer_id' => 'This customer is inactive or on credit hold.']);
            }
            if ($locked->customer->exceedsCreditLimit((float) $locked->total_amount)) {
                throw ValidationException::withMessages([
                    'customer_id' => sprintf(
                        'Credit limit exceeded: limit %s, outstanding %s, this invoice %s.',
                        number_format($locked->customer->credit_limit, 2),
                        number_format($locked->customer->outstanding_balance, 2),
                        number_format($locked->total_amount, 2)
                    )
                ]);
            }

            $this->issueInvoiceNumber($locked);

            $locked->forceFill(['submitted_at' => now(), 'submitted_by' => Auth::id()])->save();

            return $locked;
        });

        $this->workflow->submit($locked, 'sales_invoice', (float) $locked->total_amount, Auth::id());

        $invoice->refresh();
    }

    protected function issueInvoiceNumber(sales_invoice $invoice): void
    {
        if ($invoice->invoice_no) {
            return;
        }

        $seriesId = $invoice->invoice_series_id
            ?: $this->defaultSeriesForDraft($invoice->organization_id);

        if (!$seriesId) {
            throw new RuntimeException(
                'Cannot issue an invoice number: no active sales-invoice series is configured.'
            );
        }

        $series = invoice_series::whereKey($seriesId)->lockForUpdate()->first();
        if (!$series) {
            throw new RuntimeException("Invoice series #{$seriesId} not found.");
        }

        $series->assertIssuable();          // is_active, valid_until, series_end
        $number = $series->nextLabel();

        $series->forceFill(['next_number' => (int) $series->next_number + 1])->save();

        $invoice->forceFill([
            'invoice_no' => $number,
            'invoice_series_id' => $series->id,
        ])->save();
    }

    // ==================== APPROVAL ====================

    public function showApproval(sales_invoice $invoice, approval_transaction $transaction)
    {
        $this->authorizeApprover($transaction, $invoice);

        $invoice->load(['customer', 'term', 'lines.taxes.tax', 'lines.glAccount', 'lines.uom']);
        $step = $transaction->currentStep();
        $orgId = $invoice->organization_id;

        $masker = $this->masker();
        $tin = $masker->mask($invoice->bill_to_tin, 'tax_id');

        $lineVatValues = $invoice->lines->mapWithKeys(
            fn($line) => [$line->id => $this->vatValue($line, $orgId)]
        );

        $lineData = $invoice->lines->mapWithKeys(fn($line) => [
            $line->id => [
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'discount_amount' => (float) $line->discount_amount,
                'vat' => $this->vatValue($line, $orgId),
            ],
        ]);

        return view('ar.invoice.approve', [
            'invoice' => $invoice,
            'transaction' => $transaction,
            'step' => $step,
            'glAccounts' => $this->revenueAccounts($orgId),
            'vatOptions' => $this->vatOptions($orgId, $invoice->invoice_date),
            'lineVatValues' => $lineVatValues,
            'lineData' => $lineData,
            'taxOption' => (int) $invoice->tax_option,
            'tin' => $tin,
            'history' => $transaction->histories()->with('actor')->get(),
            'arSettings' => ar_setting::forCurrentOrg(),
        ]);
    }

    public function approve(Request $request, approval_transaction $transaction)
    {
        $invoice = $this->invoiceFromTransaction($transaction);
        $this->authorizeApprover($transaction, $invoice);
        $step = $transaction->currentStep();

        try {
            DB::transaction(function () use ($request, $invoice, $step, $transaction) {
                $this->applyApproverEdits($invoice, $step, (array) $request->input('lines', []), $transaction);
                $this->workflow->approve($transaction, Auth::id(), $request->input('remarks'));
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('approvals.index')->with('success', 'Invoice approved' . ($step->is_final_approval ? ' and posted.' : ' and forwarded.'));
    }

    public function returnInvoice(Request $request, approval_transaction $transaction)
    {
        $invoice = $this->invoiceFromTransaction($transaction);
        $this->authorizeApprover($transaction, $invoice);
        $remarks = $request->validate(['remarks' => 'required|string|max:1000'])['remarks'];

        try {
            $this->workflow->returnToRequester($transaction, Auth::id(), $remarks);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('approvals.index')->with('success', 'Invoice returned to the requester.');
    }

    public function rejectInvoice(Request $request, approval_transaction $transaction)
    {
        $invoice = $this->invoiceFromTransaction($transaction);
        $this->authorizeApprover($transaction, $invoice);
        $remarks = $request->validate(['remarks' => 'required|string|max:1000'])['remarks'];

        $this->workflow->reject($transaction, Auth::id(), $remarks);

        $invoice->forceFill([
            'voided_at' => now(),
            'voided_by' => Auth::id(),
            'void_reason' => 'Rejected at approval: ' . $remarks,
        ])->save();

        return redirect()->route('approvals.index')->with('success', 'Invoice rejected.');
    }

    protected function invoiceFromTransaction(approval_transaction $transaction): sales_invoice
    {
        abort_unless($transaction->approvable_type === sales_invoice::class, 404);

        return sales_invoice::findOrFail($transaction->approvable_id);
    }

    protected function authorizeApprover(approval_transaction $transaction, sales_invoice $invoice): void
    {
        abort_unless($transaction->approvable_type === sales_invoice::class && (int) $transaction->approvable_id === (int) $invoice->id, 404);
        $this->assertOwnOrganization($invoice);
        abort_unless(
            approval_transaction::pendingFor(Auth::user())->where('approval_transactions.id', $transaction->id)->exists(),
            403,
            'This invoice is not waiting on your approval.'
        );
    }

    protected function applyApproverEdits(sales_invoice $invoice, $step, array $input, approval_transaction $transaction): void
    {
        if (!($step->can_edit_chart_of_account || $step->can_edit_tax || $step->can_edit_amount) || empty($input)) {
            return;
        }

        $norm = fn(array $a) => array_map(fn($x) => is_numeric($x) ? round((float) $x, 6) : $x, $a);
        $changed = false;

        foreach ($invoice->lines()->with('taxes')->get() as $line) {
            $in = $input[$line->id] ?? null;
            if (!$in) {
                continue;
            }

            $current = [
                'item_id' => $line->item_id,
                'description' => $line->description,
                'gl_account_id' => $line->gl_account_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_amount' => $line->discount_amount,
                'uom_id' => $line->uom_id,
                'vat' => $this->vatValue($line, $invoice->organization_id),
            ];
            $new = $current;

            if ($step->can_edit_chart_of_account && isset($in['gl_account_id'])) {
                $new['gl_account_id'] = (int) $in['gl_account_id'];
            }
            if ($step->can_edit_tax && isset($in['vat'])) {
                $new['vat'] = $in['vat'];
            }
            if ($step->can_edit_amount) {
                foreach (['quantity', 'unit_price', 'discount_amount'] as $f) {
                    if (isset($in[$f]) && $in[$f] !== '') {
                        $new[$f] = $in[$f];
                    }
                }
            }

            if ($norm($new) === $norm($current)) {
                continue;
            }

            $this->writeLine($invoice, $line, (int) $line->line_no, $new);
            $changed = true;
        }

        if ($changed) {
            $invoice->recalcTotals();
            approval_transaction_history::create([
                'approval_transaction_id' => $transaction->id,
                'approval_workflow_step_id' => $step->id,
                'step_no' => $step->step_no,
                'action' => 'edited',
                'actor_id' => Auth::id(),
                'remarks' => 'Approver edited invoice lines (GL account / VAT / amounts).',
                'acted_at' => now(),
                'created_by' => Auth::id(),
            ]);
        }
    }

    // ==================== LINE / TAX LOGIC ====================

    protected function writeLine(sales_invoice $invoice, ?sales_invoice_line $line, int $lineNo, array $v): sales_invoice_line
    {
        $orgId = $invoice->organization_id;

        [$vatClass, $taxId] = $this->parseVat((string) $v['vat'], $lineNo, $orgId);
        $tax = $taxId ? $this->resolveTax((int) $taxId, $invoice->invoice_date, $lineNo, $orgId) : null;

        // FIX: tax_option drives whether we compute anything at all.
        // 0 = No Tax (force tax_amount = 0, total = taxable)
        // 1 = Tax Inclusive (price includes VAT; extract)
        // 2 = Tax Exclusive (price excludes VAT; add)
        $taxOption = (int) ($invoice->tax_option ?? 0);

        if ($taxOption === 0 && $tax) {
            // No-Tax invoice: keep the VAT class for BIR reporting but do not
            // post any tax. This lets a non-VAT seller use the same form.
            $tax = null;
        }

        if ($vatClass === 'vatable' && $taxOption !== 0 && !$tax) {
            throw ValidationException::withMessages([
                'lines' => "Line {$lineNo}: a VATable line on a tax-inclusive or tax-exclusive invoice needs a VAT rate.",
            ]);
        }

        $isRevenue = chart_of_account::where('organization_id', $orgId)->where('is_posting', true)->where('status', true)
            ->whereHas('accountType', fn($q) => $q->where('code', 'REVENUE'))->whereKey($v['gl_account_id'])->exists();
        if (!$isRevenue) {
            throw ValidationException::withMessages(['lines' => "Line {$lineNo}: choose an active revenue (posting) account."]);
        }

        $qty = round((float) $v['quantity'], 6);
        $price = round((float) $v['unit_price'], 6);
        $discount = round((float) ($v['discount_amount'] ?? 0), 2);

        if ($v['item_id'] && !item::whereKey($v['item_id'])->where('is_sellable', true)->exists()) {
            throw ValidationException::withMessages(['lines' => "Line {$lineNo}: choose a sellable item."]);
        }
        // For tax-inclusive lines the taxable base is the same (the price already
        // includes VAT), but the tax is *carved out* of it rather than added.
        // For tax-exclusive lines the taxable base is the same and the tax is
        // added. For No Tax the taxable base is the total.
        $taxable = round($qty * $price - $discount, 2);
        if ($taxable < 0) {
            throw ValidationException::withMessages(['lines' => "Line {$lineNo}: the discount is larger than the line amount."]);
        }

        $taxAmount = 0.0;
        if ($tax) {
            $taxAmount = match ($taxOption) {
                1 => $this->computeTaxInclusive($tax, $taxable),   // extract
                2 => $this->computeTax($tax, $taxable),            // add
                default => 0.0,                                    // shouldn't reach here
            };
        }

        $totalAmount = match ($taxOption) {
            1 => $taxable,                        // price already includes tax
            default => round($taxable + $taxAmount, 2), // No Tax + Exclusive
        };

        [$uomId, $uomFactor] = $this->resolveUom($v['item_id'] ?? null, $v['uom_id'] ?? null, $lineNo);

        $payload = [
            'line_no' => $lineNo,
            'item_id' => $v['item_id'] ?: null,
            'description' => $v['description'],
            'gl_account_id' => $v['gl_account_id'],
            'vat_class' => $vatClass,
            'quantity' => $qty,
            'unit_price' => $price,
            'discount_amount' => $discount,
            'taxable_amount' => $taxable,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'uom_id' => $uomId,
            // 'uom_factor' => $uomFactor,
            'updated_by' => Auth::id(),
        ];

        if ($line) {
            $line->update($payload);
            $line->taxes()->delete();
        } else {
            $line = $invoice->lines()->create($payload + ['created_by' => Auth::id()]);
        }

        if ($tax) {
            $line->taxes()->create([
                'line_no' => 1,
                'tax_id' => $tax->id,
                'taxable_amount' => $taxable,
                'tax_amount' => $taxAmount,
                'created_by' => Auth::id(),
            ]);
        }

        return $line;
    }

    protected function parseVat(string $value, int $lineNo, int $orgId): array
    {
        if (str_starts_with($value, 'tax:') && ctype_digit(substr($value, 4))) {
            $taxId = (int) substr($value, 4);
            $tax = tax_master::with('taxType')->where('organization_id', $orgId)->find($taxId);

            if ($tax && $tax->is_system) {
                return match ($tax->code) {
                    'ZERO-RATED' => ['zero_rated', null],
                    'VAT-EXEMPT' => ['exempt', null],
                    'NON-VAT' => ['non_vat', null],
                    default => ['vatable', $taxId],
                };
            }

            return ['vatable', $taxId];
        }
        if (in_array($value, ['zero_rated', 'exempt', 'non_vat'], true)) {
            return [$value, null];
        }

        throw ValidationException::withMessages(['lines' => "Line {$lineNo}: choose a VAT treatment."]);
    }

    protected function resolveTax(int $taxId, $date, int $lineNo, int $orgId): tax_master
    {
        $tax = tax_master::with(['taxType', 'taxFormula'])->where('organization_id', $orgId)->where('status', 1)->find($taxId);
        $fail = fn($why) => ValidationException::withMessages(['lines' => "Line {$lineNo}: {$why}"]);

        if (!$tax) {
            throw $fail('the selected tax is not available.');
        }
        if ($tax->taxType?->code !== 'VAT') {
            throw $fail("{$tax->code} is not a VAT tax. Withholding tax is recorded on the collection, not the invoice.");
        }
        // FIX: reject input VAT. Only output VAT (or direction = 'both') may be
        // applied to a sales invoice line. This closes the gap the form's helper
        // text warned about — the dropdown still shows both, but the server
        // refuses anything that isn't output/both.
        if (!in_array($tax->direction ?? 'both', ['output', 'both'], true)) {
            throw $fail("{$tax->code} is Input VAT and cannot be applied to a sales invoice.");
        }
        if ($tax->taxFormula?->operation !== 'Add' || !in_array($tax->taxFormula?->type, ['Percentage', 'FixedAmount'], true)) {
            throw $fail("{$tax->code} uses a formula that invoices do not support yet (inclusive or compound).");
        }
        $on = Carbon::parse($date);
        if ($on->lt($tax->effective_from) || ($tax->effective_to && $on->gt($tax->effective_to))) {
            throw $fail("{$tax->code} is not effective on {$on->format('M d, Y')}.");
        }

        return $tax;
    }

    protected function computeTax(tax_master $tax, float $taxable): float
    {
        return $tax->taxFormula->type === 'FixedAmount'
            ? round((float) $tax->fixed_amount, 2)
            : round($taxable * (float) $tax->rate / 100, 2);
    }

    protected function computeTaxInclusive(tax_master $tax, float $taxable): float
    {
        if ($tax->taxFormula->type === 'FixedAmount') {
            return 0.0;
        }
        $rate = (float) $tax->rate;

        return round($taxable * $rate / (100 + $rate), 2);
    }

    protected function resolveUom($itemId, $uomId, int $lineNo): array
    {
        if (!$itemId) {
            return [$uomId ?: null, 1.0];
        }

        $item = item::with(['uom.baseUom', 'salesUom.baseUom'])->find($itemId);
        $uom = $uomId ? unit_of_measure::with('baseUom')->find($uomId) : ($item->salesUom ?? $item->uom);

        if (!$uom || !$item->uom) {
            return [$uom?->id, 1.0];
        }
        if ($uom->rootId() !== $item->uom->rootId()) {
            throw ValidationException::withMessages(['lines' => "Line {$lineNo}: {$uom->code} cannot be converted to the item's base unit {$item->uom->code}."]);
        }

        return [$uom->id, unit_of_measure::convert(1, $uom, $item->uom)];
    }

    protected function vatValue(sales_invoice_line $line, ?int $orgId = null): string
    {
        if ($line->vat_class === 'vatable') {
            return 'tax:' . ($line->taxes->first()->tax_id ?? '');
        }

        $orgId ??= $line->invoice?->organization_id;
        if (!$orgId) {
            return $line->vat_class;
        }

        $code = match ($line->vat_class) {
            'zero_rated' => 'ZERO-RATED',
            'exempt' => 'VAT-EXEMPT',
            'non_vat' => 'NON-VAT',
            default => null,
        };
        if (!$code) {
            return $line->vat_class;
        }

        $taxId = tax_master::where('organization_id', $orgId)->where('code', $code)->value('id');

        return $taxId ? 'tax:' . $taxId : $line->vat_class;
    }

    // ==================== FORM DATA ====================

    protected function formData(sales_invoice $invoice): array
    {
        $orgId = SystemSettings::getOrganizationId();
        $date = $invoice->invoice_date ?? today();
        $vatOptions = $this->vatOptions($orgId, $date);

        $customers = customer::where('organization_id', $orgId)->where('is_active', true)
            ->withSum(['invoices as open_balance' => fn($q) => $q->open()], 'amount_due')
            ->orderBy('name')->get();

        $items = item::with(['uom', 'salesUom'])
            ->where(fn($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->where('status', 1)->where('is_sellable', true)->orderBy('code')->get();

        $uoms = unit_of_measure::with('baseUom')->where('organization_id', $orgId)->where('status', 1)->orderBy('code')->get();

        $invoice->loadMissing('lines.taxes');

        // FIX: $initialLines is the per-line payload only. The item picker options
        // belong in the TOP-LEVEL 'itemOptions' key (below), not nested per-line.
        $initialLines = $invoice->exists ? $invoice->lines->map(fn($l) => [
            'item_id' => (string) $l->item_id,
            'description' => $l->description,
            'gl_account_id' => (string) $l->gl_account_id,
            'quantity' => (float) $l->quantity,
            'unit_price' => (float) $l->unit_price,
            'discount_amount' => (float) $l->discount_amount,
            'vat' => $this->vatValue($l, $orgId),
            'uom_id' => (string) $l->uom_id,
        ])->values()->all() : [];

        $returned = null;
        if ($invoice->exists && $invoice->approval_status === '4') {
            $returned = optional($invoice->latestApprovalTransaction)->histories()->where('action', 'returned')->latest('id')->first();
        }

        return [
            'invoice' => $invoice,
            'terms' => term::where('organization_id', $orgId)->where('status', 1)->orderBy('days')->get(['id', 'name', 'days']),
            'currencies' => currency::where('status', 1)->orderBy('code')->get(['id', 'code', 'name']),
            'accounts' => $this->revenueAccounts($orgId),
            'taxOption' => (int) old('tax_option', $invoice->tax_option ?? 0),
            'vatOptions' => $vatOptions,
            'customerOptions' => $customers->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'term_id' => $c->payment_term_id,
                'currency_id' => $c->currency_id,
                'vat_class' => $c->vat_classification,
                'credit_hold' => (bool) $c->credit_hold,
                'credit_limit' => (float) $c->credit_limit,
                'open_balance' => (float) $c->open_balance,
                'default_sales_discount_account_id' => $c->default_sales_discount_account_id,
            ])->values(),
            'itemOptions' => $items->map(fn($i) => [
                'id' => $i->id,
                'code' => $i->code,
                'name' => $i->name,
                'description' => $i->description ?: $i->name,
                'uom_id' => $i->sales_uom_id ?? $i->uom_id,
                'root' => $i->uom?->rootId(),
                'gl_account_id' => $i->sales_gl_account_id,
                'vat' => $i->default_sales_tax_id ? 'tax:' . $i->default_sales_tax_id : null,
                'unit_price' => (float) ($i->default_sales_price ?? $i->default_unit_price),
                'sales_price' => $i->default_sales_price !== null ? (float) $i->default_sales_price : null,
                'base_uom_id' => $i->uom_id,
                'base_price' => $i->default_unit_price !== null ? (float) $i->default_unit_price : null,
                'has_sales_defaults' => (bool) ($i->sales_gl_account_id || $i->default_sales_tax_id || $i->default_sales_price || $i->sales_uom_id),
            ])->values(),
            'uomOptions' => $uoms->map(fn($u) => [
                'id' => $u->id,
                'code' => $u->code,
                'name' => $u->name,
                'root' => $u->rootId(),
                'rf' => $u->rootFactor(),
            ])->values(),
            'seriesOptions' => invoice_series::where('organization_id', $orgId)
                ->where('document_type', 'sales_invoice')
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('code')
                ->get(['id', 'code', 'is_default']),
            'initialLines' => $initialLines,
            'hasWorkflow' => $this->hasWorkflow($orgId),
            'returnRemark' => $returned,
            'arSettings' => ar_setting::forCurrentOrg(),
        ];
    }

    protected function revenueAccounts(int $orgId)
    {
        return chart_of_account::where('organization_id', $orgId)->where('is_posting', true)->where('status', true)
            ->whereHas('accountType', fn($q) => $q->where('code', 'REVENUE'))
            ->orderBy('account_code')->get(['id', 'account_code', 'account_name']);
    }

    protected function vatOptions(int $orgId, $date): array
    {
        return tax_master::with(['taxFormula', 'taxType'])
            ->where('organization_id', $orgId)
            ->where('status', 1)
            // Accept real VAT taxes AND the sales-classification pseudo-taxes
            ->whereHas('taxType', fn($q) => $q->whereIn('code', ['VAT', 'SALES_CLASS']))
            ->where(fn($q) => $q->where('direction', 'output')->orWhere('direction', 'both')->orWhereNull('direction'))
            ->whereHas('taxFormula', fn($q) => $q->where('operation', 'Add')->whereIn('type', ['Percentage', 'FixedAmount']))
            ->where('effective_from', '<=', Carbon::parse($date))
            ->where(fn($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', Carbon::parse($date)))
            ->orderBy('code')->get()
            ->map(fn($t) => [
                'value' => 'tax:' . $t->id,
                'label' => $t->code . ' — ' . $t->name,
                'code' => $t->code,
                'type' => $t->taxFormula->type,
                'rate' => (float) $t->rate,
                'fixed' => (float) $t->fixed_amount,
                'is_class' => $t->taxType->code === 'SALES_CLASS',
            ])->values()->all();
    }

    protected function hasWorkflow(int $orgId): bool
    {
        return approval_workflow::where('module_code', 'sales_invoice')->where('is_active', true)
            ->where(fn($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))->exists();
    }

    protected function customerAddress(customer $c): string
    {
        return collect([$c->address1, $c->address2, $c->city, $c->province, $c->zip_code])->filter()->implode(', ');
    }

    protected function assertOwnOrganization(sales_invoice $invoice): void
    {
        abort_if((int) $invoice->organization_id !== (int) SystemSettings::getOrganizationId(), 404);
    }
}