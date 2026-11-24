<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Models\approval_transaction;
use App\Models\approval_transaction_history;
use App\Models\bank_account;
use App\Models\chart_of_account;
use App\Models\collection;
use App\Models\collection_application;
use App\Models\customer;
use App\Models\payment_method;
use App\Models\sales_invoice;
use App\Models\tax_master;
use App\Models\currency;
use App\Services\ApprovalWorkflowService;
use App\Services\AuthorizationService;
use App\Services\Concerns\PostsAccountsReceivable;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CollectionController extends Controller
{
    use PostsAccountsReceivable;

    public function __construct(protected ApprovalWorkflowService $workflow)
    {
    }

    protected function authz(): AuthorizationService
    {
        return new AuthorizationService(Auth::user());
    }

    // ==================== LIST / DETAIL ====================

    public function index(Request $request)
    {
        $this->authz()->authorizeRead(Modules::AR, Modules::AR_COL);

        $orgId = SystemSettings::getOrganizationId();

        $collections = collection::with('customer')
            ->where('organization_id', $orgId)
            ->when($request->filled('searchname'), function ($q) use ($request) {
                $s = '%' . $request->searchname . '%';
                $q->where(function ($w) use ($s) {
                    $w->where('collection_no', 'like', $s)
                        ->orWhere('reference_number', 'like', $s)
                        ->orWhere('check_number', 'like', $s)
                        ->orWhereHas('customer', fn($c) => $c->where('code', 'like', $s)->orWhere('name', 'like', $s));
                });
            })
            ->when($request->filled('searchapproval'), fn($q) => $q->where('approval_status', $request->searchapproval))
            ->when($request->filled('searchstatus'), fn($q) => $q->where('status', $request->searchstatus))
            ->when($request->filled('datefrom'), fn($q) => $q->whereDate('collection_date', '>=', $request->datefrom))
            ->when($request->filled('dateto'), fn($q) => $q->whereDate('collection_date', '<=', $request->dateto))
            ->orderByDesc('collection_date')
            ->orderByDesc('id')
            ->paginate(config('app.paginate'))
            ->withQueryString();

        $base = collection::where('organization_id', $orgId);

        return view('ar.collection.index', [
            'collections' => $collections,
            'draftCount' => (clone $base)->whereIn('approval_status', ['0', '4'])->count(),
            'pendingCount' => (clone $base)->where('approval_status', '1')->count(),
            'postedCount' => (clone $base)->where('approval_status', '2')->where('status', 'posted')->count(),
            'totalReceived' => (clone $base)->where('approval_status', '2')->where('status', 'posted')->sum('amount_received'),
            'unappliedTotal' => (clone $base)->where('approval_status', '2')->where('status', 'posted')->sum('amount_unapplied'),
            'canCreate' => $this->authz()->canCreate(Modules::AR, Modules::AR_COL),
        ]);
    }

    public function show(collection $collection)
    {
        $this->authz()->authorizeRead(Modules::AR, Modules::AR_COL);
        $this->assertOwnOrganization($collection);

        $collection->load([
            'customer',
            'paymentMethod',
            'bankAccount',
            'currency',
            'cwtTax',
            'advanceAccount',
            'applications.invoice',
            'journal.lines.glAccount',
            'latestApprovalTransaction.histories.actor',
        ]);

        $tx = $collection->latestApprovalTransaction;
        $returnRemark = ($collection->approval_status === '4' && $tx)
            ? $tx->histories->where('action', 'returned')->last()
            : null;

        return view('ar.collection.show', [
            'collection' => $collection,
            'tx' => $tx,
            'returnRemark' => $returnRemark,
            'canEdit' => $this->authz()->canUpdate(Modules::AR, Modules::AR_COL) && $collection->isEditable(),
            'canSubmit' => $this->authz()->canUpdate(Modules::AR, Modules::AR_COL) && $collection->isEditable(),
            'canVoid' => $this->authz()->canUpdate(Modules::AR, Modules::AR_COL)
                && $collection->approval_status === '2'
                && $collection->status === 'posted'
                && !$collection->voided_at,
            'hasWorkflow' => $this->hasWorkflow($collection->organization_id),
        ]);
    }

    // ==================== CREATE / EDIT ====================

    public function create(Request $request)
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_COL);

        $preselectedCustomerId = $request->query('customer_id');

        return view('ar.collection.form', $this->formData(new collection([
            'collection_date' => today(),
            'exchange_rate' => 1,
            'status' => 'draft',
            'approval_status' => '0',
        ]), $preselectedCustomerId));
    }

    public function store(Request $request)
    {
        $this->authz()->authorizeCreate(Modules::AR, Modules::AR_COL);

        return $this->persist($request, null);
    }

    public function edit(collection $collection)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_COL);
        $this->assertOwnOrganization($collection);
        abort_unless($collection->isEditable() && !$collection->gl_journal_id, 403, 'Only drafts or returned collections can be edited.');

        return view('ar.collection.form', $this->formData($collection));
    }

    public function update(Request $request, collection $collection)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_COL);
        $this->assertOwnOrganization($collection);
        abort_unless($collection->isEditable() && !$collection->gl_journal_id, 403, 'Only drafts or returned collections can be edited.');

        return $this->persist($request, $collection);
    }

    // ==================== SUBMIT ====================

    public function submit(collection $collection)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_COL);
        $this->assertOwnOrganization($collection);
        abort_unless($collection->isEditable(), 403, 'Only drafts or returned collections can be submitted.');

        try {
            $this->submitForApproval($collection);
        } catch (ValidationException $e) {
            return redirect()->route('ar.collections.show', $collection)
                ->with('error', collect($e->errors())->flatten()->implode(' '));
        } catch (RuntimeException $e) {
            return redirect()->route('ar.collections.show', $collection)->with('error', $e->getMessage());
        }

        return redirect()->route('ar.collections.show', $collection)
            ->with('success', 'Collection ' . $collection->displayNo() . ' submitted for approval.');
    }

    protected function submitForApproval(collection $collection): void
    {
        // One transaction: number issuance, validation and workflow submit succeed or fail
        // together, so a failed submit never burns a BIR number.
        DB::transaction(function () use ($collection) {
            /** @var collection $locked */
            $locked = collection::whereKey($collection->id)->lockForUpdate()->firstOrFail();

            if ($locked->approval_status === '1') {
                throw ValidationException::withMessages(['approval' => 'This collection is already pending approval.']);
            }
            if (!$locked->isEditable()) {
                throw ValidationException::withMessages(['approval' => 'This collection can no longer be submitted.']);
            }
            if (!$this->hasWorkflow($locked->organization_id)) {
                throw new RuntimeException('No active approval workflow is configured for collections.');
            }

            $this->assertSubmittable($locked);

            // Number from the collection_receipt series (kept if already issued, e.g. a returned collection).
            $this->issueCollectionNo($locked);

            $locked->forceFill([
                'submitted_at' => now(),
                'submitted_by' => Auth::id(),
            ])->save();

            $this->workflow->submit($locked, 'collection', (float) $locked->amount_received, Auth::id());
        });

        $collection->refresh();
    }

    /**
     * Everything that must be true before a collection may enter approval. Posting re-checks
     * the same rules under row locks (postCollection), because invoices can change while the
     * request waits for approval.
     */
    protected function assertSubmittable(collection $collection): void
    {
        $collection->load(['customer', 'bankAccount', 'cwtTax', 'applications.invoice']);

        $received = round((float) $collection->amount_received, 2);
        $cwt = round((float) $collection->cwt_amount, 2);
        $apps = $collection->applications;

        if ($received <= 0) {
            throw ValidationException::withMessages(['amount_received' => 'Amount received must be greater than zero.']);
        }
        if ($apps->isEmpty()) {
            throw ValidationException::withMessages(['applications' => 'Add at least one invoice application before submitting.']);
        }
        if (!$collection->customer || !$collection->customer->is_active) {
            throw ValidationException::withMessages(['customer_id' => 'The customer is inactive.']);
        }
        if (!$collection->bankAccount?->chart_of_account_id) {
            throw ValidationException::withMessages(['bank_account_id' => 'The selected bank/cash account has no linked GL account.']);
        }
        if ($apps->pluck('sales_invoice_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['applications' => 'An invoice appears more than once. Combine it into one line.']);
        }

        foreach ($apps as $app) {
            $inv = $app->invoice;
            $amt = round((float) $app->amount_applied, 2);
            $w = round((float) $app->cwt_applied, 2);

            if (!$inv || (int) $inv->customer_id !== (int) $collection->customer_id) {
                throw ValidationException::withMessages(['applications' => 'An applied invoice belongs to a different customer.']);
            }
            if (!$inv->isOpen()) {
                throw ValidationException::withMessages(['applications' => "Invoice {$inv->displayNo()} is not open for collection."]);
            }
            if ($amt + $w <= 0) {
                throw ValidationException::withMessages(['applications' => "Enter an amount for invoice {$inv->displayNo()} or remove it."]);
            }
            if ($amt + $w > (float) $inv->amount_due + 0.01) {
                throw ValidationException::withMessages([
                    'applications' => "Application to invoice {$inv->displayNo()} exceeds its balance of " . number_format((float) $inv->amount_due, 2) . '.',
                ]);
            }
        }

        $applied = round((float) $apps->sum('amount_applied'), 2);
        $cwtApplied = round((float) $apps->sum('cwt_applied'), 2);
        $unapplied = round($received - $applied, 2);

        if ($unapplied < -0.01) {
            throw ValidationException::withMessages(['amount_received' => 'The amount applied is more than the amount received.']);
        }
        if (abs($cwtApplied - $cwt) > 0.01) {
            throw ValidationException::withMessages(['cwt_amount' => 'Tax withheld must be fully allocated to invoices (CWT applied must equal the CWT amount).']);
        }
        if ($cwt > 0 && !$collection->cwtTax?->gl_account_id) {
            throw ValidationException::withMessages(['cwt_tax_id' => 'Select a withholding tax whose GL account is configured.']);
        }
        if ($unapplied > 0.01 && !$collection->advance_account_id) {
            throw ValidationException::withMessages(['advance_account_id' => 'Select the credit account (e.g. Customer Deposits) for the unapplied amount.']);
        }
    }

    // ==================== APPROVAL ====================

    public function showApproval(collection $collection, approval_transaction $transaction)
    {
        $this->authorizeApprover($transaction, $collection);

        $collection->load([
            'customer',
            'paymentMethod',
            'bankAccount',
            'currency',
            'cwtTax',
            'advanceAccount',
            'applications.invoice',
        ]);

        $step = $transaction->currentStep();
        $orgId = $collection->organization_id;

        return view('ar.collection.approve', [
            'collection' => $collection,
            'transaction' => $transaction,
            'step' => $step,
            'history' => $transaction->histories()->with('actor')->get(),
        ]);
    }

    public function approve(Request $request, approval_transaction $transaction)
    {
        $collection = $this->collectionFromTransaction($transaction);
        $this->authorizeApprover($transaction, $collection);
        $step = $transaction->currentStep();

        try {
            DB::transaction(function () use ($request, $collection, $step, $transaction) {
                $this->applyApproverEdits($collection, $step, (array) $request->input('applications', []), $transaction);
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
            'Collection approved' . ($step->is_final_approval ? ' and posted.' : ' and forwarded.')
        );
    }

    public function returnCollection(Request $request, approval_transaction $transaction)
    {
        $collection = $this->collectionFromTransaction($transaction);
        $this->authorizeApprover($transaction, $collection);
        $remarks = $request->validate(['remarks' => 'required|string|max:1000'])['remarks'];

        try {
            $this->workflow->returnToRequester($transaction, Auth::id(), $remarks);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('approvals.index')->with('success', 'Collection returned to the requester.');
    }

    public function rejectCollection(Request $request, approval_transaction $transaction)
    {
        $collection = $this->collectionFromTransaction($transaction);
        $this->authorizeApprover($transaction, $collection);
        $remarks = $request->validate(['remarks' => 'required|string|max:1000'])['remarks'];

        $this->workflow->reject($transaction, Auth::id(), $remarks);

        $collection->forceFill([
            'status' => 'voided',
            'voided_at' => now(),
            'voided_by' => Auth::id(),
            'void_reason' => 'Rejected at approval: ' . $remarks,
        ])->save();

        return redirect()->route('approvals.index')->with('success', 'Collection rejected.');
    }

    protected function collectionFromTransaction(approval_transaction $transaction): collection
    {
        abort_unless($transaction->approvable_type === collection::class, 404);

        return collection::findOrFail($transaction->approvable_id);
    }

    protected function authorizeApprover(approval_transaction $transaction, collection $collection): void
    {
        abort_unless(
            $transaction->approvable_type === collection::class
            && (int) $transaction->approvable_id === (int) $collection->id,
            404
        );
        $this->assertOwnOrganization($collection);
        abort_unless(
            approval_transaction::pendingFor(Auth::user())->where('approval_transactions.id', $transaction->id)->exists(),
            403,
            'This collection is not waiting on your approval.'
        );
    }

    /**
     * Apply the approver's edits to the collection applications, gated by the
     * step's can_edit_amount permission. Returns early if the approver has no
     * edit rights or sent no applications.
     */
    protected function applyApproverEdits(collection $collection, $step, array $input, approval_transaction $transaction): void
    {
        if (!($step->can_edit_amount || $step->can_edit_chart_of_account || $step->can_edit_tax) || empty($input)) {
            return;
        }

        $norm = fn($a) => array_map(fn($x) => is_numeric($x) ? round((float) $x, 2) : $x, $a);
        $changed = false;

        foreach ($collection->applications()->get() as $app) {
            $in = $input[$app->id] ?? null;
            if (!$in) {
                continue;
            }

            $current = [
                'amount_applied' => (float) $app->amount_applied,
                'cwt_applied' => (float) $app->cwt_applied,
            ];
            $new = $current;

            if ($step->can_edit_amount) {
                if (isset($in['amount_applied']) && $in['amount_applied'] !== '') {
                    $new['amount_applied'] = round((float) $in['amount_applied'], 2);
                }
                if (isset($in['cwt_applied']) && $in['cwt_applied'] !== '') {
                    $new['cwt_applied'] = round((float) $in['cwt_applied'], 2);
                }
            }

            if ($norm($new) == $norm($current)) {
                continue;
            }

            // Validate against the invoice's current balance
            $invoice = $app->invoice;
            $maxAllowed = (float) $invoice->amount_due;
            if ($new['amount_applied'] + $new['cwt_applied'] > $maxAllowed + 0.01) {
                throw ValidationException::withMessages([
                    'applications' => "Application to invoice {$invoice->displayNo()} exceeds its balance of "
                        . number_format($maxAllowed, 2) . '.',
                ]);
            }

            $app->update($new);
            $changed = true;
        }

        if ($changed) {
            // Recompute amount_applied / amount_unapplied / cwt_amount from the edited rows
            $collection->recalcApplied();
            $collection->refresh();

            if ((float) $collection->amount_unapplied < -0.01) {
                throw ValidationException::withMessages([
                    'applications' => 'The edited amounts exceed the amount received ('
                        . number_format((float) $collection->amount_received, 2) . ').',
                ]);
            }
            if ((float) $collection->amount_unapplied > 0.01 && !$collection->advance_account_id) {
                throw ValidationException::withMessages([
                    'applications' => 'The edit leaves an unapplied amount, but no advance/deposit account was selected on this collection.',
                ]);
            }

            approval_transaction_history::create([
                'approval_transaction_id' => $transaction->id,
                'approval_workflow_step_id' => $step->id,
                'step_no' => $step->step_no,
                'action' => 'edited',
                'actor_id' => Auth::id(),
                'remarks' => 'Approver edited collection application amounts.',
                'acted_at' => now(),
                'created_by' => Auth::id(),
            ]);
        }
    }

    // ==================== VOID ====================

    public function void(Request $request, collection $collection)
    {
        $this->authz()->authorizeUpdate(Modules::AR, Modules::AR_COL);
        $this->assertOwnOrganization($collection);

        if ($collection->approval_status !== '2' || $collection->status !== 'posted' || $collection->voided_at) {
            return back()->with('error', 'Only approved, posted, non-voided collections can be voided.');
        }

        $reason = $request->validate(['void_reason' => 'required|string|max:255'])['void_reason'];

        try {
            DB::transaction(function () use ($collection, $reason) {
                $collection->reverseInvoiceApplications();

                $collection->forceFill([
                    'status' => 'voided',
                    'voided_at' => now(),
                    'voided_by' => Auth::id(),
                    'void_reason' => $reason,
                ])->save();
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('ar.collections.show', $collection)
            ->with('success', 'Collection voided; invoice balances restored.');
    }

    // ==================== PERSIST ====================

    protected function persist(Request $request, ?collection $collection)
    {
        $orgId = SystemSettings::getOrganizationId();

        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'collection_date' => 'required|date',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:100',
            'check_number' => 'nullable|string|max:50',
            'check_bank' => 'nullable|string|max:100',
            'check_date' => 'nullable|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|gt:0',
            'amount_received' => 'required|numeric|gt:0',
            'cwt_tax_id' => 'nullable|exists:tax_masters,id',
            'cwt_amount' => 'nullable|numeric|min:0',
            'advance_account_id' => 'nullable|exists:chart_of_accounts,id',
            'remarks' => 'nullable|string|max:2000',
            'applications' => 'array',
            'applications.*.sales_invoice_id' => 'required|exists:sales_invoices,id',
            'applications.*.amount_applied' => 'required|numeric|min:0',
            'applications.*.cwt_applied' => 'nullable|numeric|min:0',
            'action' => 'nullable|string|in:draft,submit',
        ]);

        $customer = customer::where('organization_id', $orgId)->where('is_active', true)->find($data['customer_id']);
        if (!$customer) {
            throw ValidationException::withMessages(['customer_id' => 'Select an active customer.']);
        }

        $saved = DB::transaction(function () use ($data, $collection, $customer, $orgId) {
            $attrs = [
                'customer_id' => $customer->id,
                'collection_date' => $data['collection_date'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'bank_account_id' => $data['bank_account_id'],
                'reference_number' => $data['reference_number'] ?? null,
                'check_number' => $data['check_number'] ?? null,
                'check_bank' => $data['check_bank'] ?? null,
                'check_date' => $data['check_date'] ?? null,
                'currency_id' => $data['currency_id'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'amount_received' => round((float) $data['amount_received'], 2),
                'cwt_tax_id' => $data['cwt_tax_id'] ?? null,
                'cwt_amount' => round((float) ($data['cwt_amount'] ?? 0), 2),
                'advance_account_id' => $data['advance_account_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ];

            if ($collection) {
                $collection->update($attrs);
                $collection->applications()->delete();
            } else {
                $attrs['organization_id'] = $orgId;
                $attrs['status'] = 'draft';
                $attrs['approval_status'] = '0';
                $attrs['created_by'] = Auth::id();
                $collection = collection::create($attrs);
            }

            $appliedTotal = 0;
            foreach ($data['applications'] ?? [] as $app) {
                $amt = round((float) $app['amount_applied'], 2);
                $w = round((float) ($app['cwt_applied'] ?? 0), 2);
                if ($amt <= 0 && $w <= 0) {
                    continue;
                }
                $collection->applications()->create([
                    'sales_invoice_id' => $app['sales_invoice_id'],
                    'amount_applied' => $amt,
                    'cwt_applied' => $w,
                ]);
                $appliedTotal += $amt;
            }

            $collection->forceFill([
                'amount_applied' => round($appliedTotal, 2),
                'amount_unapplied' => round((float) $data['amount_received'] - $appliedTotal, 2),
            ])->save();

            return $collection;
        });

        if (($data['action'] ?? null) === 'submit') {
            try {
                $this->submitForApproval($saved);
            } catch (ValidationException $e) {
                return redirect()->route('ar.collections.edit', $saved)->withInput()->withErrors($e->errors())
                    ->with('success', 'Saved as draft, but it could not be submitted.');
            } catch (RuntimeException $e) {
                return redirect()->route('ar.collections.show', $saved)
                    ->with('error', 'Saved as draft, but it could not be submitted: ' . $e->getMessage());
            }

            return redirect()->route('ar.collections.show', $saved)
                ->with('success', 'Collection ' . $saved->displayNo() . ' submitted for approval.');
        }

        return redirect()->route('ar.collections.show', $saved)
            ->with('success', 'Collection saved as draft.');
    }

    public function destroy(collection $collection)
    {
        $this->authz()->authorizeDelete(Modules::AR, Modules::AR_COL);
        $this->assertOwnOrganization($collection);

        if ($collection->approval_status !== '0' || $collection->gl_journal_id || $collection->submitted_at) {
            return back()->with('error', 'Only drafts that were never submitted can be deleted.');
        }

        $collection->applications()->delete();
        $collection->delete();

        return redirect()->route('ar.col')
            ->with('success', 'Draft collection deleted.');
    }

    // ==================== FORM DATA ====================

    protected function formData(collection $collection, $preselectedCustomerId = null): array
    {
        $orgId = SystemSettings::getOrganizationId();

        $customers = customer::where('organization_id', $orgId)->where('is_active', true)
            ->orderBy('name')->get([
                    'id',
                    'code',
                    'name',
                    'currency_id',
                    'payment_term_id',
                    'payment_method_id'
                ]);

        $bankAccounts = bank_account::where('organization_id', $orgId)->where('status', 1)
            ->orderBy('name')->get();

        $paymentMethods = payment_method::where('organization_id', $orgId)->where('status', 1)
            ->orderBy('name')->get();

        $cwtTaxes = tax_master::where('organization_id', $orgId)->where('status', 1)
            ->whereHas('taxType', fn($q) => $q->where('code', 'CWT'))
            ->orderBy('rate')->get();

        $advanceAccounts = chart_of_account::where('organization_id', $orgId)
            ->where('is_posting', true)->where('status', true)
            ->whereHas('accountSubcategory', fn($q) => $q->whereIn('description', [
                'Customer Deposits',
                'Advances from Customers',
                'Unearned Revenue',
            ]))
            ->orderBy('account_code')->get([
                    'id',
                    'account_code',
                    'account_name'
                ]);

        // ALL open invoices for the org — the form filters client-side by customer.
        // Simpler than a per-customer endpoint, and the row count for typical orgs is small.
        $openInvoices = sales_invoice::where('organization_id', $orgId)
            ->open()
            ->orderBy('due_date')
            ->get(['id', 'customer_id', 'invoice_no', 'invoice_date', 'due_date', 'amount_due']);

        $initialApplications = $collection->exists
            ? $collection->applications->map(fn($a) => [
                'sales_invoice_id' => $a->sales_invoice_id,
                'amount_applied' => (float) $a->amount_applied,
                'cwt_applied' => (float) $a->cwt_applied,
            ])->values()->all()
            : [];

        return [
            'collection' => $collection,
            'customers' => $customers,
            'bankAccounts' => $bankAccounts,
            'paymentMethods' => $paymentMethods,
            'cwtTaxes' => $cwtTaxes,
            'advanceAccounts' => $advanceAccounts,
            'currencies' => currency::where('status', 1)->orderBy('code')->get(['id', 'code', 'name']),
            'openInvoices' => $openInvoices,
            'initialApplications' => $initialApplications,
            'preselectedCustomerId' => $preselectedCustomerId,
            'hasWorkflow' => $this->hasWorkflow($orgId),
        ];
    }

    protected function hasWorkflow(int $orgId): bool
    {
        return \App\Models\approval_workflow::where('module_code', 'collection')
            ->where('is_active', true)
            ->where(fn($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->exists();
    }

    protected function assertOwnOrganization(collection $collection): void
    {
        abort_if((int) $collection->organization_id !== (int) SystemSettings::getOrganizationId(), 404);
    }
}