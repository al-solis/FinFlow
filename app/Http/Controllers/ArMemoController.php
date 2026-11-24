<?php

namespace App\Http\Controllers;

use RuntimeException;
use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Services\AccountingService;
use App\Services\ApprovalWorkflowService;
use App\Services\AuthorizationService;
use App\Services\Concerns\PostsAccountsReceivable;
use App\Services\SystemSettings;
use App\Traits\MasksPii;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\approval_transaction;
use App\Models\approval_transaction_history;
use App\Models\approval_workflow;
use App\Models\ar_setting;
use App\Models\chart_of_account;
use App\Models\currency;
use App\Models\customer;
use App\Models\invoice_series;
use App\Models\item;
use App\Models\unit_of_measure;
use App\Models\sales_invoice;
use App\Models\tax_master;

/**
 * Shared engine for Credit Memos and Debit Memos (AR side).
 *
 * Flow (identical to Sales Invoice / Collection):
 *   create -> Save Draft (no number)  |  Submit for Approval
 *   submit -> validate everything, issue the BIR number from invoice_series, enter the workflow
 *   approvers -> may edit GL account / VAT / amounts when their step allows it
 *   FINAL approver -> ApprovalWorkflowService posts the journal (PostsAccountsReceivable)
 *
 * Subclasses only supply cfg().
 */
abstract class ArMemoController extends Controller
{
    use MasksPii;
    use PostsAccountsReceivable;

    public function __construct(
        protected ApprovalWorkflowService $workflow,
        protected AccountingService $accounting,
    ) {
    }

    /** @return array<string,mixed> */
    abstract protected function cfg(): array;

    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    protected function masker()
    {
        return $this->getPiiMasker()->setContext(Modules::AR, $this->cfg()['module']);
    }

    protected function isCredit(): bool
    {
        return $this->cfg()['type'] === 'credit';
    }

    protected function find($id): Model
    {
        $class = $this->cfg()['model'];
        $memo = $class::findOrFail($id);
        $this->assertOwnOrganization($memo);

        return $memo;
    }

    protected function assertOwnOrganization(Model $memo): void
    {
        abort_if((int) $memo->organization_id !== (int) SystemSettings::getOrganizationId(), 404);
    }

    protected function memoDate(Model $memo)
    {
        return $memo->{$this->cfg()['date_col']};
    }

    // ==================== LIST / DETAIL ====================

    public function index(Request $request)
    {
        $c = $this->cfg();
        $authz = $this->authz();
        $authz->authorizeRead(Modules::AR, $c['module']);

        $masker = $this->masker();
        $orgId = SystemSettings::getOrganizationId();
        $class = $c['model'];

        $memos = $class::with(['customer', 'originalInvoice'])->where('organization_id', $orgId)
            ->when($request->filled('searchname'), function ($q) use ($request, $c, $masker) {
                $s = '%' . $request->searchname . '%';
                $q->where(function ($w) use ($s, $c, $masker) {
                    $w->where($c['no_col'], 'like', $s)
                        ->orWhereHas('originalInvoice', fn($i) => $i->where('invoice_no', 'like', $s))
                        ->orWhereHas('customer', function ($cu) use ($s, $masker) {
                            $cu->where('code', 'like', $s);
                            if ($masker->hasFullAccess()) {
                                $cu->orWhere('name', 'like', $s);
                            }
                        });
                });
            })
            ->when($request->filled('searchapproval'), fn($q) => $q->where('approval_status', $request->searchapproval))
            ->when($request->filled('datefrom'), fn($q) => $q->whereDate($c['date_col'], '>=', $request->datefrom))
            ->when($request->filled('dateto'), fn($q) => $q->whereDate($c['date_col'], '<=', $request->dateto))
            ->orderByDesc($c['date_col'])->orderByDesc('id')
            ->paginate(config('app.paginate'))
            ->withQueryString();

        $memos->getCollection()->each(
            fn($m) => $m->setAttribute('customer_display', $masker->mask($m->customer->name ?? '', 'full_name'))
        );

        $base = $class::where('organization_id', $orgId);
        $posted = (clone $base)->where('approval_status', '2')->whereNull('voided_at');

        return view('ar.memo.index', [
            'cfg' => $c,
            'memos' => $memos,
            'draftCount' => (clone $base)->whereIn('approval_status', ['0', '4'])->count(),
            'pendingCount' => (clone $base)->where('approval_status', '1')->count(),
            'postedCount' => (clone $posted)->count(),
            'postedTotal' => (clone $posted)->sum('total_amount'),
            'canCreate' => $authz->canCreate(Modules::AR, $c['module']),
        ]);
    }

    public function show($memo)
    {
        $c = $this->cfg();
        $authz = $this->authz();
        $authz->authorizeRead(Modules::AR, $c['module']);
        $memo = $this->find($memo);

        $masker = $this->masker();
        $memo->load([
            'customer',
            'currency',
            'series',
            'originalInvoice',
            'lines.taxes.tax',
            'lines.glAccount',
            'applications.invoice',
            'journal.lines.glAccount',
            'latestApprovalTransaction.histories.actor',
        ]);

        $cu = $memo->customer;
        $billTo = [
            'name' => $masker->mask($memo->bill_to_name ?: ($cu->legal_name ?: $cu->name), 'full_name'),
            'tin' => $masker->mask($memo->bill_to_tin ?: $cu->tax_id, 'tax_id'),
            'address' => $masker->mask($memo->bill_to_address ?: $this->customerAddress($cu), 'address'),
        ];

        $tx = $memo->latestApprovalTransaction;
        $returnRemark = ($memo->approval_status === '4' && $tx) ? $tx->histories->where('action', 'returned')->last() : null;

        return view('ar.memo.show', [
            'cfg' => $c,
            'memo' => $memo,
            'billTo' => $billTo,
            'reasonLabel' => $c['reasons'][$memo->reason_code] ?? ($memo->reason_code ?: '—'),
            'tx' => $tx,
            'returnRemark' => $returnRemark,
            'canEdit' => $authz->canUpdate(Modules::AR, $c['module']) && $memo->isEditable(),
            'canDelete' => $authz->canDelete(Modules::AR, $c['module']) && $memo->approval_status === '0'
                && !$memo->gl_journal_id && !$memo->{$c['no_col']},
            'canVoid' => $authz->canDelete(Modules::AR, $c['module']) && $memo->approval_status === '2'
                && $memo->posted_at !== null && !$memo->voided_at,
            'hasWorkflow' => $this->hasWorkflow($memo->organization_id),
        ]);
    }

    // ==================== CREATE / EDIT ====================

    public function create(Request $request)
    {
        $c = $this->cfg();
        $this->authz()->authorizeCreate(Modules::AR, $c['module']);

        $class = $c['model'];
        $memo = new $class([
            $c['date_col'] => today(),
            'exchange_rate' => 1,
            'tax_option' => 0,
        ]);
        if ($request->filled('invoice_id')) {
            $inv = sales_invoice::where('organization_id', SystemSettings::getOrganizationId())->find($request->invoice_id);
            if ($inv) {
                $memo->customer_id = $inv->customer_id;
                $memo->original_invoice_id = $inv->id;
                $memo->tax_option = $inv->tax_option;
            }
        }

        return view('ar.memo.form', $this->formData($memo));
    }

    public function store(Request $request)
    {
        $this->authz()->authorizeCreate(Modules::AR, $this->cfg()['module']);

        return $this->persist($request, null);
    }

    public function edit($memo)
    {
        $this->authz()->authorizeUpdate(Modules::AR, $this->cfg()['module']);
        $memo = $this->find($memo);
        abort_unless($memo->isEditable() && !$memo->gl_journal_id, 403, 'Only draft or returned memos can be edited.');

        return view('ar.memo.form', $this->formData($memo));
    }

    public function update(Request $request, $memo)
    {
        $this->authz()->authorizeUpdate(Modules::AR, $this->cfg()['module']);
        $memo = $this->find($memo);
        abort_unless($memo->isEditable() && !$memo->gl_journal_id, 403, 'Only draft or returned memos can be edited.');

        return $this->persist($request, $memo);
    }

    public function destroy($memo)
    {
        $c = $this->cfg();
        $this->authz()->authorizeDelete(Modules::AR, $c['module']);
        $memo = $this->find($memo);

        if ($memo->approval_status !== '0' || $memo->gl_journal_id || $memo->{$c['no_col']}) {
            return redirect()->route($c['route'] . '.show', $memo)
                ->with('error', 'Only drafts that were never submitted can be deleted. A numbered document must stay on record.');
        }

        DB::transaction(function () use ($memo) {
            foreach ($memo->lines as $line) {
                $line->taxes()->delete();
            }
            $memo->lines()->delete();
            $memo->applications()->delete();
            $memo->delete();
        });

        return redirect()->route($c['index_route'])->with('success', 'Draft ' . strtolower($c['label']) . ' deleted.');
    }

    protected function persist(Request $request, ?Model $memo)
    {
        $c = $this->cfg();
        $orgId = SystemSettings::getOrganizationId();

        // Shape/type rules live in the FormRequest (StoreCreditMemoRequest / StoreDebitMemoRequest);
        // the business rules below need the database.
        $data = app($c['request'])->validated();

        $customer = customer::where('organization_id', $orgId)->where('is_active', true)->find($data['customer_id']);
        if (!$customer) {
            throw ValidationException::withMessages(['customer_id' => 'Select an active customer.']);
        }

        // Credit memo: the invoice is optional (a trade rebate may be standalone).
        // Debit memo: required, because a debit memo is collected through its invoice.
        $invoice = null;
        if (!empty($data['invoice_id'])) {
            $invoice = sales_invoice::where('organization_id', $orgId)->find($data['invoice_id']);
            if (!$invoice || (int) $invoice->customer_id !== (int) $customer->id) {
                throw ValidationException::withMessages(['invoice_id' => 'The reference invoice must belong to the selected customer.']);
            }
            if ($invoice->approval_status !== '2' || $invoice->voided_at) {
                throw ValidationException::withMessages(['invoice_id' => 'The reference invoice must be an approved, non-voided invoice.']);
            }
            if (Carbon::parse($data['memo_date'])->lt($invoice->invoice_date)) {
                throw ValidationException::withMessages(['memo_date' => 'The memo date cannot be earlier than the invoice date (' . $invoice->invoice_date->format('M d, Y') . ').']);
            }
        } elseif (!$this->isCredit()) {
            throw ValidationException::withMessages(['invoice_id' => 'Select the invoice this debit memo adds to. Without it, the charge cannot be collected.']);
        }

        $seriesId = null;
        if (!empty($data['series_id'])) {
            $seriesId = invoice_series::where('organization_id', $orgId)
                ->where('document_type', $c['series_type'])->whereKey($data['series_id'])->value('id');
            if (!$seriesId) {
                throw ValidationException::withMessages(['series_id' => 'Select a ' . strtolower($c['label']) . ' series.']);
            }
        }
        $seriesId ??= $this->defaultSeriesForDraft($orgId);

        $class = $c['model'];
        $reason = $c['reasons'];

        $saved = DB::transaction(function () use ($data, $memo, $customer, $invoice, $orgId, $seriesId, $c, $class, $reason) {
            $attrs = [
                'customer_id' => $customer->id,
                'original_invoice_id' => $invoice?->id,
                'bill_to_name' => $customer->legal_name ?: $customer->name,
                'bill_to_tin' => $customer->tax_id,
                'bill_to_address' => mb_substr($this->customerAddress($customer), 0, 500),
                $c['date_col'] => $data['memo_date'],
                'currency_id' => $data['currency_id'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'tax_option' => (int) $data['tax_option'],
                'reason_code' => $data['reason_code'],
                'reason' => $reason[$data['reason_code']],
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ];

            if ($memo) {
                // A number already issued (returned memo) pins the series.
                if (!$memo->{$c['no_col']}) {
                    $attrs[$c['series_col']] = $seriesId;
                }
                $memo->update($attrs);
            } else {
                $memo = $class::create($attrs + [
                    'organization_id' => $orgId,
                    'status' => '0', //draft
                    'approval_status' => '0',
                    $c['series_col'] => $seriesId,
                    'amount_applied' => 0,
                    'amount_unapplied' => 0,
                    'created_by' => Auth::id(),
                ]);
            }

            // Upsert by line_no so line ids survive edits (approver-edit history stays valid).
            $existing = $memo->lines()->get()->keyBy('line_no');
            $seen = [];
            foreach (array_values($data['lines']) as $i => $line) {
                $lineNo = $i + 1;
                $seen[] = $lineNo;
                $this->writeLine($memo, $existing->get($lineNo), $lineNo, $line);
            }
            foreach ($memo->lines()->whereNotIn('line_no', $seen)->get() as $stale) {
                $stale->taxes()->delete();
                $stale->delete();
            }
            $memo->recalcTotals();

            // Credit memo: optionally settle against the original invoice's open balance.
            $memo->applications()->delete();
            if ($this->isCredit() && $invoice && !empty($data['apply_to_invoice'])) {
                $amt = round(min((float) $memo->total_amount, max((float) $invoice->amount_due, 0)), 2);
                if ($amt > 0) {
                    $memo->applications()->create(['sales_invoice_id' => $invoice->id, 'amount_applied' => $amt]);
                }
            }

            return $memo;
        });

        if (($data['action'] ?? null) === 'submit') {
            try {
                $this->submitForApproval($saved);
            } catch (ValidationException $e) {
                return redirect()->route($c['route'] . '.edit', $saved)->withInput()->withErrors($e->errors())
                    ->with('success', 'Saved as draft, but it could not be submitted.');
            } catch (RuntimeException $e) {
                return redirect()->route($c['route'] . '.show', $saved)
                    ->with('error', 'Saved as draft, but it could not be submitted: ' . $e->getMessage());
            }

            return redirect()->route($c['route'] . '.show', $saved)
                ->with('success', $c['label'] . ' ' . $saved->displayNo() . ' submitted for approval.');
        }

        return redirect()->route($c['route'] . '.show', $saved)->with('success', $c['label'] . ' saved as draft.');
    }

    protected function defaultSeriesForDraft(int $orgId): ?int
    {
        return invoice_series::where('organization_id', $orgId)
            ->where('document_type', $this->cfg()['series_type'])
            ->where('is_active', true)
            ->orderByDesc('is_default')->orderBy('id')
            ->value('id');
    }

    // ==================== SUBMIT ====================

    public function submit($memo)
    {
        $c = $this->cfg();
        $this->authz()->authorizeUpdate(Modules::AR, $c['module']);
        $memo = $this->find($memo);
        abort_unless($memo->isEditable(), 403, 'Only draft or returned memos can be submitted.');

        try {
            $this->submitForApproval($memo);
        } catch (ValidationException $e) {
            return redirect()->route($c['route'] . '.show', $memo)->with('error', collect($e->errors())->flatten()->implode(' '));
        } catch (RuntimeException $e) {
            return redirect()->route($c['route'] . '.show', $memo)->with('error', $e->getMessage());
        }

        return redirect()->route($c['route'] . '.show', $memo)
            ->with('success', $c['label'] . ' ' . $memo->displayNo() . ' submitted for approval.');
    }

    protected function submitForApproval(Model $memo): void
    {
        $c = $this->cfg();

        // One transaction: validation, number issuance and the workflow submit succeed or
        // fail together, so a failed submit never burns a BIR number.
        DB::transaction(function () use ($memo, $c) {
            $class = $c['model'];
            $locked = $class::whereKey($memo->id)->lockForUpdate()->firstOrFail();

            if ($locked->approval_status === '1') {
                throw ValidationException::withMessages(['approval' => 'This ' . strtolower($c['label']) . ' is already pending approval.']);
            }
            if (!$locked->isEditable()) {
                throw ValidationException::withMessages(['approval' => 'This ' . strtolower($c['label']) . ' can no longer be submitted.']);
            }
            if (!$this->hasWorkflow($locked->organization_id)) {
                throw new RuntimeException('No active approval workflow is configured for ' . strtolower($c['plural']) . '.');
            }

            $this->assertSubmittable($locked);

            // Number from the series (kept if already issued, e.g. a returned memo that is resubmitted).
            $this->issueMemoNo($locked, $c['series_type'], $c['no_col'], $c['series_col']);

            $locked->forceFill(
                [
                    'submitted_at' => now(),
                    'submitted_by' => Auth::id()
                ]
            )->save();

            $this->workflow->submit($locked, $c['workflow'], (float) $locked->total_amount, Auth::id());
        });

        $memo->refresh();
    }

    protected function assertSubmittable(Model $memo): void
    {
        $memo->load(['customer', 'lines', 'applications']);

        if ($memo->lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Add at least one line before submitting.']);
        }
        if ((float) $memo->total_amount <= 0) {
            throw ValidationException::withMessages(['lines' => 'The memo total must be greater than zero.']);
        }
        if (!$memo->customer || !$memo->customer->is_active) {
            throw ValidationException::withMessages(['customer_id' => 'The customer is inactive.']);
        }
        if (!array_key_exists((string) $memo->reason_code, $this->cfg()['reasons'])) {
            throw ValidationException::withMessages(['reason_code' => 'Select the reason this memo is issued.']);
        }

        // Original invoice, credit cap, application vs balance: same rules posting enforces under lock.
        try {
            $this->assertMemoAgainstInvoice($memo, $this->cfg()['type']);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['invoice_id' => $e->getMessage()]);
        }
    }

    // ==================== APPROVAL ====================

    public function showApproval($memo, approval_transaction $transaction)
    {
        $c = $this->cfg();
        $memo = $this->find($memo);
        $this->authorizeApprover($transaction, $memo);

        $memo->load(['customer', 'currency', 'originalInvoice', 'lines.taxes.tax', 'lines.glAccount', 'applications']);
        $step = $transaction->currentStep();
        $orgId = $memo->organization_id;
        $masker = $this->masker();

        $lineVatValues = $memo->lines->mapWithKeys(fn($l) => [$l->id => $this->vatValue($l, $orgId)]);
        $lineData = $memo->lines->mapWithKeys(fn($l) => [
            $l->id => [
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'discount_amount' => (float) $l->discount_amount,
                'vat' => $this->vatValue($l, $orgId),
            ]
        ]);

        $inv = $memo->originalInvoice;

        return view('ar.memo.approve', [
            'cfg' => $c,
            'memo' => $memo,
            'transaction' => $transaction,
            'step' => $step,
            'glAccounts' => $this->accountOptions($orgId),
            'vatOptions' => $this->vatOptions($orgId, $this->memoDate($memo)),
            'lineVatValues' => $lineVatValues,
            'lineData' => $lineData,
            'taxOption' => (int) $memo->tax_option,
            'tin' => $masker->mask($memo->bill_to_tin, 'tax_id'),
            'billToName' => $masker->mask($memo->bill_to_name, 'full_name'),
            'invoice' => $inv,
            'reasonLabel' => $c['reasons'][$memo->reason_code] ?? ($memo->reason_code ?: '—'),
            'appliedAmount' => (float) $memo->applications->sum('amount_applied'),
            'history' => $transaction->histories()->with('actor')->get(),
        ]);
    }

    public function approve(Request $request, approval_transaction $transaction)
    {
        $memo = $this->memoFromTransaction($transaction);
        $this->authorizeApprover($transaction, $memo);
        $step = $transaction->currentStep();

        try {
            DB::transaction(function () use ($request, $memo, $step, $transaction) {
                $this->applyApproverEdits($memo, $step, $request, $transaction);
                $this->workflow->approve($transaction, Auth::id(), $request->input('remarks'));
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('approvals.index')->with(
            'success',
            $this->cfg()['label'] . ' approved' . ($step->is_final_approval ? ' and posted.' : ' and forwarded.')
        );
    }

    public function returnMemo(Request $request, approval_transaction $transaction)
    {
        $memo = $this->memoFromTransaction($transaction);
        $this->authorizeApprover($transaction, $memo);
        $remarks = $request->validate(['remarks' => 'required|string|max:1000'])['remarks'];

        try {
            $this->workflow->returnToRequester($transaction, Auth::id(), $remarks);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('approvals.index')->with('success', $this->cfg()['label'] . ' returned to the requester.');
    }

    public function rejectMemo(Request $request, approval_transaction $transaction)
    {
        $memo = $this->memoFromTransaction($transaction);
        $this->authorizeApprover($transaction, $memo);
        $remarks = $request->validate(['remarks' => 'required|string|max:1000'])['remarks'];

        $this->workflow->reject($transaction, Auth::id(), $remarks);

        // The number was issued on submit: keep the document on record as voided.
        $memo->forceFill([
            'voided_at' => now(),
            'voided_by' => Auth::id(),
            'void_reason' => 'Rejected at approval: ' . $remarks,
        ])->save();

        return redirect()->route('approvals.index')->with('success', $this->cfg()['label'] . ' rejected.');
    }

    protected function memoFromTransaction(approval_transaction $transaction): Model
    {
        $class = $this->cfg()['model'];
        abort_unless($transaction->approvable_type === $class, 404);

        return $class::findOrFail($transaction->approvable_id);
    }

    protected function authorizeApprover(approval_transaction $transaction, Model $memo): void
    {
        $class = $this->cfg()['model'];
        abort_unless($transaction->approvable_type === $class && (int) $transaction->approvable_id === (int) $memo->id, 404);
        $this->assertOwnOrganization($memo);
        abort_unless(
            approval_transaction::pendingFor(Auth::user())->where('approval_transactions.id', $transaction->id)->exists(),
            403,
            'This ' . strtolower($this->cfg()['label']) . ' is not waiting on your approval.'
        );
    }

    /**
     * Approver edits, gated by the step's flags:
     *   can_edit_chart_of_account -> line GL account
     *   can_edit_tax              -> line VAT treatment
     *   can_edit_amount           -> quantity / unit price / discount (and, on a credit memo,
     *                                the amount applied to the original invoice)
     */
    protected function applyApproverEdits(Model $memo, $step, Request $request, approval_transaction $transaction): void
    {
        if (!($step->can_edit_chart_of_account || $step->can_edit_tax || $step->can_edit_amount)) {
            return;
        }

        $input = (array) $request->input('lines', []);
        $norm = fn(array $a) => array_map(fn($x) => is_numeric($x) ? round((float) $x, 6) : $x, $a);
        $changed = false;

        foreach ($memo->lines()->with('taxes')->get() as $line) {
            $in = $input[$line->id] ?? null;
            if (!$in) {
                continue;
            }

            $current = [
                'description' => $line->description,
                'gl_account_id' => $line->gl_account_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_amount' => $line->discount_amount,
                'vat' => $this->vatValue($line, $memo->organization_id),
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

            $this->writeLine($memo, $line, (int) $line->line_no, $new);
            $changed = true;
        }

        $notes = [];
        if ($changed) {
            $memo->recalcTotals();
            $memo->refresh();
            $notes[] = 'lines (GL account / VAT / amounts)';
        }

        // Credit memo: the portion settled against the original invoice.
        if ($this->isCredit() && $memo->original_invoice_id) {
            $app = $memo->applications()->first();
            $currentApplied = $app ? round((float) $app->amount_applied, 2) : 0.0;
            $target = $currentApplied;

            if ($step->can_edit_amount && $request->filled('applied')) {
                $target = round(max((float) $request->input('applied'), 0), 2);
            }
            // The application can never exceed the (possibly reduced) memo total.
            $target = round(min($target, (float) $memo->total_amount), 2);

            if (abs($target - $currentApplied) > 0.004) {
                $memo->applications()->delete();
                if ($target > 0) {
                    $memo->applications()->create(['sales_invoice_id' => $memo->original_invoice_id, 'amount_applied' => $target]);
                }
                $notes[] = 'amount applied to the invoice (' . number_format($currentApplied, 2) . ' -> ' . number_format($target, 2) . ')';
                $changed = true;
            }
        }

        if ($changed) {
            // Re-run the business rules so a bad edit fails here, with a clear message, not at posting.
            try {
                $this->assertMemoAgainstInvoice($memo->fresh(['applications']), $this->cfg()['type']);
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages(['lines' => $e->getMessage()]);
            }

            approval_transaction_history::create([
                'approval_transaction_id' => $transaction->id,
                'approval_workflow_step_id' => $step->id,
                'step_no' => $step->step_no,
                'action' => 'edited',
                'actor_id' => Auth::id(),
                'remarks' => 'Approver edited: ' . implode('; ', $notes) . '.',
                'acted_at' => now(),
                'created_by' => Auth::id(),
            ]);
        }
    }

    // ==================== VOID (after posting) ====================

    public function void(Request $request, $memo)
    {
        $c = $this->cfg();
        $this->authz()->authorizeDelete(Modules::AR, $c['module']);
        $memo = $this->find($memo);

        //dd($memo->approval_status, $memo->posted_at, $memo->voided_at);

        if ($memo->approval_status !== '2' || $memo->posted_at === null || $memo->voided_at) {
            return back()->with('error', 'Only approved, posted, non-voided memos can be voided.');
        }

        $reason = $request->validate(['void_reason' => 'required|string|max:255'])['void_reason'];

        try {
            $this->accounting->voidMemoPosting($memo, $c['type'], Auth::id(), $reason);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route($c['route'] . '.show', $memo)
            ->with('success', $c['label'] . ' voided; a reversing journal was posted and the invoice balance restored.');
    }

    // ==================== LOOKUP FOR THE FORM ====================

    /** JSON: the original invoice's lines, so the memo can start from them. */
    public function invoiceLines($invoice)
    {
        $c = $this->cfg();
        $this->authz()->authorizeRead(Modules::AR, $c['module']);
        $orgId = SystemSettings::getOrganizationId();

        $inv = sales_invoice::with('lines.taxes')->where('organization_id', $orgId)->findOrFail($invoice);
        $returnAccount = $this->isCredit()
            ? ar_setting::where('organization_id', $orgId)->value('default_sales_return_account_id')
            : null;

        return response()->json([
            'tax_option' => (int) $inv->tax_option,
            'total' => (float) $inv->total_amount,
            'balance' => (float) $inv->amount_due,
            'lines' => $inv->lines->map(fn($l) => [
                'item_id' => $l->item_id ? (string) $l->item_id : '',
                'uom_id' => $l->uom_id ? (string) $l->uom_id : '',
                'description' => $l->description,
                'gl_account_id' => (string) ($returnAccount ?: $l->gl_account_id),
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'discount_amount' => (float) $l->discount_amount,
                'vat' => $this->vatValue($l, $orgId),
            ])->values(),
        ]);
    }

    // ==================== LINE / TAX LOGIC (same rules as the sales invoice) ====================

    protected function writeLine(Model $memo, $line, int $lineNo, array $v)
    {
        $orgId = $memo->organization_id;
        $date = $this->memoDate($memo);

        [$vatClass, $taxId] = $this->parseVat((string) $v['vat'], $lineNo, $orgId);
        $tax = $taxId ? $this->resolveTax((int) $taxId, $date, $lineNo, $orgId) : null;

        // 0 = No Tax, 1 = Tax Inclusive (extract), 2 = Tax Exclusive (add)
        $taxOption = (int) ($memo->tax_option ?? 0);
        if ($taxOption === 0 && $tax) {
            $tax = null;
        }
        if ($vatClass === 'vatable' && $taxOption !== 0 && !$tax) {
            throw ValidationException::withMessages([
                'lines' => "Line {$lineNo}: a VATable line on a tax-inclusive or tax-exclusive memo needs a VAT rate.",
            ]);
        }

        $isRevenue = chart_of_account::where('organization_id', $orgId)->where('is_posting', true)->where('status', true)
            ->whereHas('accountType', fn($q) => $q->where('code', 'REVENUE'))->whereKey($v['gl_account_id'])->exists();
        if (!$isRevenue) {
            throw ValidationException::withMessages(['lines' => "Line {$lineNo}: choose an active revenue / contra-revenue (posting) account."]);
        }

        // Optional item (free-text lines have none). Falls back to what the line already has,
        // so an approver edit that does not send the item keeps it.
        $itemId = array_key_exists('item_id', $v) ? $v['item_id'] : ($line->item_id ?? null);
        $itemId = $itemId ? (int) $itemId : null;
        if ($itemId && !$this->inOrg(item::query())->where('status', 1)->whereKey($itemId)->exists()) {
            throw ValidationException::withMessages(['lines' => "Line {$lineNo}: the selected item is not available."]);
        }
        $uomId = array_key_exists('uom_id', $v) ? $v['uom_id'] : ($line->uom_id ?? null);
        $uomId = $uomId ? (int) $uomId : null;

        $qty = round((float) $v['quantity'], 6);
        $price = round((float) $v['unit_price'], 6);
        $discount = round((float) ($v['discount_amount'] ?? 0), 2);

        $taxable = round($qty * $price - $discount, 2);
        if ($taxable < 0) {
            throw ValidationException::withMessages(['lines' => "Line {$lineNo}: the discount is larger than the line amount."]);
        }

        $taxAmount = 0.0;
        if ($tax) {
            $taxAmount = match ($taxOption) {
                1 => $this->computeTaxInclusive($tax, $taxable),
                2 => $this->computeTax($tax, $taxable),
                default => 0.0,
            };
        }
        $totalAmount = $taxOption === 1 ? $taxable : round($taxable + $taxAmount, 2);

        $payload = [
            'line_no' => $lineNo,
            'item_id' => $itemId,
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
            'updated_by' => Auth::id(),
        ];

        if ($line) {
            $line->update($payload);
            $line->taxes()->delete();
        } else {
            $line = $memo->lines()->create($payload + ['created_by' => Auth::id()]);
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
            throw $fail("{$tax->code} is not a VAT tax. Withholding tax is recorded on the collection, not on a memo.");
        }
        if (!in_array($tax->direction ?? 'both', ['output', 'both'], true)) {
            throw $fail("{$tax->code} is Input VAT and cannot be applied to a sales memo.");
        }
        if ($tax->taxFormula?->operation !== 'Add' || !in_array($tax->taxFormula?->type, ['Percentage', 'FixedAmount'], true)) {
            throw $fail("{$tax->code} uses a formula that memos do not support yet (inclusive or compound).");
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

    /** The value the VAT <select> uses for a stored line (also feeds the approver screen). */
    protected function vatValue($line, ?int $orgId = null): string
    {
        if ($line->vat_class === 'vatable') {
            return 'tax:' . ($line->taxes->first()->tax_id ?? '');
        }

        if (!$orgId) {
            return (string) $line->vat_class;
        }

        $code = match ($line->vat_class) {
            'zero_rated' => 'ZERO-RATED',
            'exempt' => 'VAT-EXEMPT',
            'non_vat' => 'NON-VAT',
            default => null,
        };
        if (!$code) {
            return (string) $line->vat_class;
        }

        $taxId = tax_master::where('organization_id', $orgId)->where('code', $code)->value('id');

        return $taxId ? 'tax:' . $taxId : (string) $line->vat_class;
    }

    // ==================== FORM DATA ====================

    protected function formData(Model $memo): array
    {
        $c = $this->cfg();
        $orgId = SystemSettings::getOrganizationId();
        $date = $this->memoDate($memo) ?? today();

        $memo->loadMissing('lines.taxes', 'applications');

        $customers = customer::where('organization_id', $orgId)->where('is_active', true)->orderBy('name')->get();

        $invoices = sales_invoice::where('organization_id', $orgId)
            ->where('approval_status', '2')->whereNull('voided_at')
            ->orderByDesc('invoice_date')->orderByDesc('id')->limit(1500)
            ->get(['id', 'customer_id', 'invoice_no', 'invoice_date', 'total_amount', 'amount_due', 'tax_option']);

        $initialLines = $memo->exists ? $memo->lines->map(fn($l) => [
            'item_id' => $l->item_id ? (string) $l->item_id : '',
            'uom_id' => $l->uom_id ? (string) $l->uom_id : '',
            'description' => $l->description,
            'gl_account_id' => (string) $l->gl_account_id,
            'quantity' => (float) $l->quantity,
            'unit_price' => (float) $l->unit_price,
            'discount_amount' => (float) $l->discount_amount,
            'vat' => $this->vatValue($l, $orgId),
        ])->values()->all() : [];

        $returned = null;
        if ($memo->exists && $memo->approval_status === '4') {
            $returned = optional($memo->latestApprovalTransaction)->histories()->where('action', 'returned')->latest('id')->first();
        }

        return [
            'cfg' => $c,
            'memo' => $memo,
            'isEdit' => $memo->exists,
            'memoDate' => $date,
            'currencies' => currency::where('status', 1)->orderBy('code')->get(['id', 'code', 'name']),
            'accounts' => $this->accountOptions($orgId),
            'vatOptions' => $this->vatOptions($orgId, $date),
            'customerOptions' => $customers->map(fn($cu) => [
                'id' => $cu->id,
                'code' => $cu->code,
                'name' => $cu->name,
                'currency_id' => $cu->currency_id,
                'vat_class' => $cu->vat_classification,
            ])->values(),
            'invoiceOptions' => $invoices->map(fn($i) => [
                'id' => $i->id,
                'customer_id' => $i->customer_id,
                'no' => $i->invoice_no,
                'date' => optional($i->invoice_date)->format('M d, Y'),
                'total' => (float) $i->total_amount,
                'balance' => (float) $i->amount_due,
                'tax_option' => (int) $i->tax_option,
            ])->values(),
            'seriesOptions' => invoice_series::where('organization_id', $orgId)
                ->where('document_type', $c['series_type'])->where('is_active', true)
                ->orderByDesc('is_default')->orderBy('code')->get(['id', 'code', 'is_default']),
            'initialLines' => $initialLines,
            'applyToInvoice' => $memo->exists ? $memo->applications->sum('amount_applied') > 0 : true,
            'defaultAccountId' => $this->isCredit()
                ? ar_setting::where('organization_id', $orgId)->value('default_sales_return_account_id')
                : null,
            'items' => $this->itemOptions($orgId),
            'uoms' => unit_of_measure::where('organization_id', $orgId)->get(['id', 'code', 'name'])
                ->mapWithKeys(fn($u) => [$u->id => $u->code ?: $u->name]),
            'hasWorkflow' => $this->hasWorkflow($orgId),
            'returnRemark' => $returned,
            'invoiceLinesUrl' => route($c['route'] . '.invoice-lines', ['invoice' => '__ID__']),
        ];
    }

    /** items.organization_id is nullable: NULL means shared with every organization. */
    protected function inOrg($query)
    {
        $orgId = SystemSettings::getOrganizationId();

        return $query->where(fn($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'));
    }

    /**
     * Sellable, active items with their selling defaults. Picking one fills the line; the line
     * can still be edited or left as free text.
     */
    protected function itemOptions(int $orgId): array
    {
        $returnAccount = $this->isCredit()
            ? ar_setting::where('organization_id', $orgId)->value('default_sales_return_account_id')
            : null;

        return $this->inOrg(item::query())
            ->where('status', 1)->where('is_sellable', true)
            ->orderBy('code')->get()
            ->map(fn($i) => [
                'id' => (string) $i->id,
                'code' => $i->code,
                'name' => $i->name,
                'description' => $i->description ?: $i->name,
                'uom_id' => (string) ($i->sales_uom_id ?: $i->uom_id),
                // Credit memos go to the Sales Returns account when one is set in A/R Settings.
                'gl_account_id' => (string) ($returnAccount ?: $i->sales_gl_account_id),
                'vat' => $i->default_sales_tax_id ? 'tax:' . $i->default_sales_tax_id : '',
                'unit_price' => (float) $i->default_sales_price,
            ])->values()->all();
    }

    protected function accountOptions(int $orgId)
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
        return approval_workflow::where('module_code', $this->cfg()['workflow'])->where('is_active', true)
            ->where(fn($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->whereHas('steps')->exists();
    }

    protected function customerAddress(customer $cu): string
    {
        return collect([$cu->address1, $cu->address2, $cu->city, $cu->province, $cu->zip_code])->filter()->implode(', ');
    }
}
