<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\CashAdvanceLiquidation;
use App\Models\CashAdvanceLiquidationDetail;
use App\Constants\Modules;
use App\Services\ApprovalWorkflowService;
use App\Services\AccountingService;
use App\Traits\WithSystemSettings;
use App\Traits\AuthorizesAccessRights;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Models\approval_workflow;
use App\Models\approval_workflow_step;
use App\Models\chart_of_account;
use App\Models\CashAdvanceRefund;
use App\Models\ReimbursementDetail;
use App\Models\approval_transaction;
use App\Models\approval_transaction_history;
use App\Models\CashAdvanceLiquidationAttachment;

class CashAdvanceController extends Controller
{
    use AuthorizesAccessRights;
    use WithSystemSettings;

    protected ApprovalWorkflowService $approvals;
    protected AccountingService $accounting;

    public function __construct(ApprovalWorkflowService $approvals, AccountingService $accounting)
    {
        $this->approvals = $approvals;
        $this->accounting = $accounting;
    }

    /**
     * Check if an approver is set up for a module
     */
    private function hasApproverSetup(string $moduleCode, float $amount): bool
    {
        $workflow = approval_workflow::resolveFor($moduleCode, $amount, $this->getOrganizationId());
        return $workflow && $workflow->steps()->exists();
    }

    private function getOrganizationId()
    {
        $settings = \App\Services\SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    // ==================== CASH ADVANCE ====================

    public function index(Request $request)
    {
        $this->authorizeRead(Modules::CM, Modules::CM_CA);

        $user = Auth::user();

        $cashAdvances = CashAdvance::query()
            ->with(['employee', 'creator', 'glAccount'])
            ->when($user->role_id != 1, function ($q) use ($user) {
                // Users can only see their own CAs unless they're admin
                return $q->where('employee_id', $user->id);
            })
            ->when($request->filled('searchstatus'), fn($q) => $q->where('approval_status', $request->searchstatus))
            ->orderByDesc('id')
            ->paginate(config('app.paginate', 15));

        return view('cm.ca.index', compact('cashAdvances'));
    }

    public function create()
    {
        $this->authorizeCreate(Modules::CM, Modules::CM_CA);

        // Check if approver is set up BEFORE allowing creation
        $hasApprover = $this->hasApproverSetup('ca', 0);
        $allAccounts = $this->getGlAccounts();
        $assetAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'ASSET';
        })->values();

        return view('cm.ca.form', $this->withSystemSettings([
            'cashAdvance' => new CashAdvance(),
            'hasApprover' => $hasApprover,
            'glAccounts' => $assetAccounts,
        ]));
    }

    public function store(Request $request)
    {
        $this->authorizeCreate(Modules::CM, Modules::CM_CA);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'gl_account_id' => 'required|exists:chart_of_accounts,id',
            'purpose' => 'required|string|max:500',
            'expected_liquidation_date' => 'nullable|date|after:today',
        ]);

        // Check approver setup before allowing submission
        $hasApprover = $this->hasApproverSetup('ca', $validated['amount']);
        if (!$hasApprover) {
            return back()->with('error', 'No approver configured for Cash Advances. Please contact administrator.');
        }

        DB::transaction(function () use ($validated, $request) {
            $ca = CashAdvance::create([
                'organization_id' => $this->getOrganizationId(),
                'employee_id' => Auth::id(),
                'amount' => $validated['amount'],
                'gl_account_id' => $validated['gl_account_id'],
                'purpose' => $validated['purpose'],
                'expected_liquidation_date' => $validated['expected_liquidation_date'] ?? null,
                'status' => '0',
                'approval_status' => '0',
                'liquidated_amount' => 0,
                'created_by' => Auth::id(),
            ]);

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($ca, 'ca', (float) $ca->amount, Auth::id());
                $ca->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('cm.ca')->with('success', 'Cash Advance saved.');
    }

    public function edit(CashAdvance $cashAdvance)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_CA);
        // dd($cashAdvance);
        $user = Auth::user();
        $allAccounts = $this->getGlAccounts();
        $assetAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'ASSET';
        })->values();

        // Only allow editing if draft or returned
        if (!in_array($cashAdvance->approval_status, ['0', '4'])) {
            abort(403, 'Only draft or returned Cash Advances can be edited.');
        }

        // User can only edit their own CAs
        if ($cashAdvance->employee_id != $user->id) {
            abort(403, 'You can only edit your own Cash Advances. CA belongs to user: ');
        }

        // Check approver comments
        $approverComments = approval_transaction::with('histories')->where('approvable_id', $cashAdvance->id)
            ->where('approvable_type', CashAdvance::class)
            ->whereHas('histories', function ($query) {
                $query->where('action', 'returned');
            })
            ->orderByDesc('created_at')
            ->first();
        // dd($approverComments);
        return view('cm.ca.form', $this->withSystemSettings([
            'cashAdvance' => $cashAdvance,
            'hasApprover' => $this->hasApproverSetup('ca', (float) $cashAdvance->amount),
            'glAccounts' => $assetAccounts,
            'approverComments' => $approverComments,
        ]));
    }

    public function update(Request $request, CashAdvance $cashAdvance)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_CA);

        if (!in_array($cashAdvance->approval_status, ['0', '4'])) {
            abort(403, 'Only draft or returned Cash Advances can be edited.');
        }

        if ($cashAdvance->employee_id != Auth::id()) {
            abort(403, 'You can only edit your own Cash Advances.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'gl_account_id' => 'required|exists:chart_of_accounts,id',
            'purpose' => 'required|string|max:500',
            'expected_liquidation_date' => 'nullable|date|after:today',
        ]);

        DB::transaction(function () use ($validated, $request, $cashAdvance) {
            $cashAdvance->update([
                'amount' => $validated['amount'],
                'gl_account_id' => $validated['gl_account_id'],
                'purpose' => $validated['purpose'],
                'expected_liquidation_date' => $validated['expected_liquidation_date'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($cashAdvance->fresh(), 'ca', (float) $cashAdvance->amount, Auth::id());
                $cashAdvance->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('cm.ca')->with('success', 'Cash Advance updated.');
    }

    // ==================== LIQUIDATION ====================
    public function liquidationsIndex(Request $request)
    {
        $this->authorizeRead(Modules::CM, Modules::CM_LIQ);

        $user = Auth::user();

        $liquidations = CashAdvanceLiquidation::query()
            ->with(['cashAdvance', 'employee', 'creator', 'details', 'latestApprovalTransaction'])
            ->when(!$user->isAdmin(), function ($q) use ($user) {
                // Users can only see their own liquidations unless they're admin
                return $q->where('employee_id', $user->id);
            })
            ->when($request->filled('searchstatus'), function ($q) use ($request) {
                return $q->where('approval_status', $request->searchstatus);
            })
            ->when($request->filled('searchca'), function ($q) use ($request) {
                // Search by cash advance ID
                return $q->where('cash_advance_id', $request->searchca);
            })
            ->when($request->filled('datefrom'), function ($q) use ($request) {
                return $q->whereDate('liquidation_date', '>=', $request->datefrom);
            })
            ->when($request->filled('dateto'), function ($q) use ($request) {
                return $q->whereDate('liquidation_date', '<=', $request->dateto);
            })
            ->orderByDesc('id')
            ->paginate(config('app.paginate', 15));

        return view('cm.liquidation.index', compact('liquidations'));
    }

    public function createLiquidation(CashAdvance $cashAdvance)
    {
        $this->authorizeCreate(Modules::CM, Modules::CM_LIQ);

        if ($cashAdvance->employee_id != Auth::id()) {
            abort(403, 'You can only liquidate your own Cash Advances.');
        }
        if ($cashAdvance->approval_status !== '2') {
            abort(403, 'This Cash Advance is not approved for liquidation.');
        }
        if ((float) $cashAdvance->liquidated_amount >= (float) $cashAdvance->disbursed_amount) {
            abort(403, 'This Cash Advance is already fully liquidated.');
        }

        $hasApprover = $this->hasApproverSetup('liquidation', 0);
        if (!$hasApprover) {
            return back()->with('error', 'No approver configured for Liquidations. Please contact administrator.');
        }

        // Amount tied up in liquidations already submitted but not yet approved/rejected/returned
        $pendingLiquidationAmount = (float) CashAdvanceLiquidation::where('cash_advance_id', $cashAdvance->id)
            ->where('approval_status', '1') // pending
            ->sum('total_expenses');

        //check if there is any pending refunds
        $pendingRefundAmount = (float) CashAdvanceRefund::where('cash_advance_id', $cashAdvance->id)
            ->whereIn('approval_status', ['1', '2']) // pending/approved
            ->sum('amount');

        $remainingAmount = (float) $cashAdvance->disbursed_amount
            - (float) $cashAdvance->liquidated_amount
            - $pendingLiquidationAmount
            - $pendingRefundAmount;

        if ($remainingAmount <= 0) {
            abort(403, 'No remaining amount available for liquidation.');
        }

        // Get Expense accounts for debit (EXPENSE or COST type)
        $allAccounts = $this->getGlAccounts();
        $expenseAccounts = $allAccounts->filter(fn($a) => $a->accountType && in_array($a->accountType->code, ['EXPENSE', 'COST']))->values();

        // Get Liability accounts for credit (Reimbursement Payable)
        $creditAccounts = $allAccounts->filter(fn($a) => $a->accountType && $a->accountType->code === 'LIABILITY')->values();

        return view('cm.liquidation.form', $this->withSystemSettings([
            'cashAdvance' => $cashAdvance,
            'liquidation' => new CashAdvanceLiquidation(),
            'isEdit' => false,
            'remainingAmount' => max($remainingAmount, 0),
            'pendingLiquidationAmount' => $pendingLiquidationAmount ?? 0,
            'pendingRefundAmount' => $pendingRefundAmount ?? 0,
            'glAccounts' => $expenseAccounts, // Debit accounts
            'creditAccounts' => $creditAccounts, // Credit accounts (Reimbursement Payable)
            'creditAccountId' => null,
        ]));
    }

    public function storeLiquidation(Request $request, CashAdvance $cashAdvance)
    {
        $this->authorizeCreate(Modules::CM, Modules::CM_LIQ);

        if ($cashAdvance->employee_id != Auth::id()) {
            abort(403, 'You can only liquidate your own Cash Advances.');
        }
        if ($cashAdvance->approval_status !== '2') {
            abort(403, 'This Cash Advance is not approved for liquidation.');
        }

        $validated = $request->validate([
            'liquidation_date' => 'required|date',
            'remarks' => 'nullable|string|max:500',
            'details' => 'required|array|min:1',
            'details.*.expense_date' => 'required|date',
            'details.*.description' => 'required|string|max:255',
            'details.*.gl_account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.amount' => 'required|numeric|min:0.01',
            'details.*.reference' => 'nullable|string|max:50',
            'credit_account_id' => 'nullable|exists:chart_of_accounts,id',
            'attachment_files.*' => 'nullable|file|max:20480',
            'attachment_descriptions.*' => 'nullable|string|max:255',
        ]);

        $pendingLiquidationAmount = (float) CashAdvanceLiquidation::where('cash_advance_id', $cashAdvance->id)
            ->where('approval_status', '1')
            ->sum('total_expenses');

        $remainingAmount = (float) $cashAdvance->disbursed_amount
            - (float) $cashAdvance->liquidated_amount
            - $pendingLiquidationAmount;

        $totalExpenses = collect($validated['details'])->sum('amount');
        $excessAmount = max(0, $totalExpenses - $remainingAmount);

        // Validate credit_account_id is provided when there's excess
        if ($excessAmount > 0 && empty($validated['credit_account_id'])) {
            return back()->withErrors([
                'credit_account_id' => 'Please select a Reimbursement Payable account for the excess amount.'
            ])->withInput();
        }
        // if ($totalExpenses > $remainingAmount) {
        //     $message = $pendingLiquidationAmount > 0
        //         ? "Total expenses ({$totalExpenses}) exceed the available balance ({$remainingAmount}). "
        //         . "Note: " . number_format($pendingLiquidationAmount, 2) . " is already tied up in a liquidation pending approval for this Cash Advance."
        //         : "Total expenses ({$totalExpenses}) exceed remaining amount ({$remainingAmount}).";

        //     return back()->withErrors(['details' => $message])->withInput();
        // }

        if (!$this->hasApproverSetup('liquidation', $totalExpenses)) {
            return back()->with('error', 'No approver configured for Liquidations. Please contact administrator.');
        }

        DB::transaction(function () use ($validated, $cashAdvance, $totalExpenses, $request) {
            $liquidation = CashAdvanceLiquidation::create([
                'organization_id' => $this->getOrganizationId(),
                'cash_advance_id' => $cashAdvance->id,
                'employee_id' => Auth::id(),
                'liquidation_date' => $validated['liquidation_date'],
                'total_expenses' => $totalExpenses,
                'credit_account_id' => $request->credit_account_id ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => '0',
                'approval_status' => '0',
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['details'] as $detail) {
                CashAdvanceLiquidationDetail::create([
                    'liquidation_id' => $liquidation->id,
                    'expense_date' => $detail['expense_date'],
                    'description' => $detail['description'],
                    'gl_account_id' => $detail['gl_account_id'],
                    'amount' => $detail['amount'],
                    'reference' => $detail['reference'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            $this->syncLiquidationAttachments(
                $liquidation,
                $request->file('attachment_files', []),
                $request->input('attachment_descriptions', [])
            );

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($liquidation, 'liquidation', (float) $totalExpenses, Auth::id());
                $liquidation->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('cm.liq')->with('success', 'Liquidation saved.');
    }

    protected function syncLiquidationAttachments(CashAdvanceLiquidation $liquidation, array $files, array $descriptions): void
    {
        foreach ($descriptions as $index => $description) {
            if (!isset($files[$index]) || !$files[$index]->isValid()) {
                continue;
            }

            $file = $files[$index];
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;
            $filePath = 'liquidations/' . $liquidation->id . '/' . $fileName;

            Storage::disk('private')->putFileAs('liquidations/' . $liquidation->id, $file, $fileName);

            CashAdvanceLiquidationAttachment::create([
                'liquidation_id' => $liquidation->id,
                'file_name' => $fileName,
                'original_filename' => $originalName,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $description ?? null,
                'uploaded_by' => Auth::id(),
                'created_by' => Auth::id(),
            ]);
        }
    }

    public function editLiquidation(CashAdvanceLiquidation $liquidation)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_LIQ);

        if ($liquidation->employee_id != Auth::id()) {
            abort(403, 'You can only edit your own liquidations.');
        }
        if (!in_array($liquidation->approval_status, ['0', '4'])) {
            abort(403, 'Only draft or returned liquidations can be edited.');
        }

        $liquidation->load(['details', 'cashAdvance']);
        $cashAdvance = $liquidation->cashAdvance;

        // Balance tied up in OTHER pending liquidations against this CA — excludes this one,
        // since its current amount isn't "competing" for balance while it's being re-edited.
        $pendingLiquidationAmount = (float) CashAdvanceLiquidation::where('cash_advance_id', $cashAdvance->id)
            ->where('id', '!=', $liquidation->id)
            ->where('approval_status', '1')
            ->sum('total_expenses');

        $remainingAmount = (float) $cashAdvance->disbursed_amount
            - (float) $cashAdvance->liquidated_amount
            - $pendingLiquidationAmount;

        $allAccounts = $this->getGlAccounts();
        $expenseAccounts = $allAccounts->filter(fn($a) => $a->accountType && in_array($a->accountType->code, ['EXPENSE', 'COST']))->values();
        $creditAccounts = $allAccounts->filter(fn($a) => $a->accountType && $a->accountType->code === 'LIABILITY')->values();

        $lines = $liquidation->details->map(fn($d) => [
            '_key' => (string) $d->id,
            'id' => $d->id,
            'expense_date' => optional($d->expense_date)->format('Y-m-d'),
            'description' => $d->description,
            'gl_account_id' => $d->gl_account_id,
            'amount' => (float) $d->amount,
            'reference' => $d->reference,
        ])->values()->all();

        // Calculate excess amount for this liquidation
        $totalExpenses = (float) $liquidation->total_expenses;
        $excessAmount = max(0, $totalExpenses - $remainingAmount);

        return view('cm.liquidation.form', $this->withSystemSettings([
            'cashAdvance' => $cashAdvance,
            'liquidation' => $liquidation,
            'isEdit' => true,
            'lines' => $lines,
            'remainingAmount' => max($remainingAmount, 0),
            'pendingLiquidationAmount' => $pendingLiquidationAmount ?? 0,
            'glAccounts' => $expenseAccounts,
            'creditAccounts' => $creditAccounts,
            'creditAccountId' => $liquidation->credit_account_id,
            'excessAmount' => $excessAmount,
            'totalExpenses' => $totalExpenses,
        ]));
    }

    public function updateLiquidation(Request $request, CashAdvanceLiquidation $liquidation)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_LIQ);

        if ($liquidation->employee_id != Auth::id()) {
            abort(403, 'You can only edit your own liquidations.');
        }
        if (!in_array($liquidation->approval_status, ['0', '4'])) {
            abort(403, 'Only draft or returned liquidations can be edited.');
        }

        $validated = $request->validate([
            'liquidation_date' => 'required|date',
            'remarks' => 'nullable|string|max:500',
            'details' => 'required|array|min:1',
            'details.*.expense_date' => 'required|date',
            'details.*.description' => 'required|string|max:255',
            'details.*.gl_account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.amount' => 'required|numeric|min:0.01',
            'details.*.reference' => 'nullable|string|max:50',
            'credit_account_id' => 'nullable|exists:chart_of_accounts,id',
            'attachment_files.*' => 'nullable|file|max:20480',
            'attachment_descriptions.*' => 'nullable|string|max:255',
        ]);

        $cashAdvance = $liquidation->cashAdvance;

        $pendingLiquidationAmount = (float) CashAdvanceLiquidation::where('cash_advance_id', $cashAdvance->id)
            ->where('id', '!=', $liquidation->id)
            ->where('approval_status', '1')
            ->sum('total_expenses');

        $remainingAmount = (float) $cashAdvance->disbursed_amount
            - (float) $cashAdvance->liquidated_amount
            - $pendingLiquidationAmount;

        $totalExpenses = collect($validated['details'])->sum('amount');
        $excessAmount = max(0, $totalExpenses - $remainingAmount);

        // Validate credit_account_id is provided when there's excess
        if ($excessAmount > 0 && empty($validated['credit_account_id'])) {
            return back()->withErrors([
                'credit_account_id' => 'Please select a Reimbursement Payable account for the excess amount.'
            ])->withInput();
        }

        if (!$this->hasApproverSetup('liquidation', $totalExpenses)) {
            return back()->with('error', 'No approver configured for Liquidations. Please contact administrator.');
        }

        DB::transaction(function () use ($validated, $liquidation, $totalExpenses, $excessAmount, $request) {
            $updateData = [
                'liquidation_date' => $validated['liquidation_date'],
                'remarks' => $validated['remarks'] ?? null,
                'total_expenses' => $totalExpenses,
                'updated_by' => Auth::id(),
            ];

            // Save credit_account_id only if there's excess
            if ($excessAmount > 0 && !empty($validated['credit_account_id'])) {
                $updateData['credit_account_id'] = $validated['credit_account_id'];
            } else {
                $updateData['credit_account_id'] = null;
            }

            $liquidation->update($updateData);

            // Replace all lines
            $liquidation->details()->delete();

            foreach ($validated['details'] as $detail) {
                CashAdvanceLiquidationDetail::create([
                    'liquidation_id' => $liquidation->id,
                    'expense_date' => $detail['expense_date'],
                    'description' => $detail['description'],
                    'gl_account_id' => $detail['gl_account_id'],
                    'amount' => $detail['amount'],
                    'reference' => $detail['reference'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            $this->syncLiquidationAttachments(
                $liquidation,
                $request->file('attachment_files', []),
                $request->input('attachment_descriptions', [])
            );

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($liquidation->fresh(), 'liquidation', (float) $totalExpenses, Auth::id());
                $liquidation->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('cm.liq')->with('success', 'Liquidation updated.');
    }

    /**
     * Download liquidation attachment
     */
    public function downloadLiquidationAttachment(CashAdvanceLiquidationAttachment $attachment)
    {
        $this->authorizeRead(Modules::CM, Modules::CM_LIQ);

        // Verify user has access to this liquidation
        $liquidation = $attachment->liquidation;
        if (!$liquidation) {
            abort(404, 'Liquidation not found.');
        }

        // Check if user owns this liquidation or is admin
        if (!$this->isAdmin() && $liquidation->employee_id != Auth::id()) {
            abort(403, 'You do not have permission to view this attachment.');
        }

        if (!Storage::disk('private')->exists($attachment->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('private')->download(
            $attachment->file_path,
            $attachment->original_filename
        );
    }

    /**
     * Delete liquidation attachment
     */
    public function deleteLiquidationAttachment(CashAdvanceLiquidationAttachment $attachment)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_LIQ);

        $liquidation = $attachment->liquidation;
        if (!$liquidation) {
            abort(404, 'Liquidation not found.');
        }

        // Only owner or admin can delete attachments
        if (!$this->isAdmin() && $liquidation->employee_id != Auth::id()) {
            abort(403, 'You can only delete attachments from your own liquidations.');
        }

        // Only allow deletion if liquidation is draft or returned
        if (!in_array($liquidation->approval_status, ['0', '4'])) {
            abort(403, 'Attachments can only be deleted from draft or returned liquidations.');
        }

        DB::transaction(function () use ($attachment) {
            if (Storage::disk('private')->exists($attachment->file_path)) {
                Storage::disk('private')->delete($attachment->file_path);
            }
            $attachment->delete();
        });

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Attachment deleted.');
    }

    public function showLiquidationApproval(CashAdvanceLiquidation $liquidation, approval_transaction $transaction)
    {
        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $liquidation->load(['details.glAccount', 'cashAdvance', 'employee']);

        $allAccounts = $this->getGlAccounts();
        $expenseAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && in_array($account->accountType->code, ['EXPENSE', 'COST']);
        })->values();

        $creditAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'LIABILITY';
        })->values();

        // Context for the approver: how much of the CA's balance is spoken for elsewhere
        $pendingElsewhere = (float) CashAdvanceLiquidation::where('cash_advance_id', $liquidation->cash_advance_id)
            ->where('id', '!=', $liquidation->id)
            ->where('approval_status', '1')
            ->sum('total_expenses');

        $availableBalance = (float) $liquidation->cashAdvance->disbursed_amount
            - (float) $liquidation->cashAdvance->liquidated_amount
            - $pendingElsewhere;

        $excessAmount = max(0, $liquidation->total_expenses - $availableBalance);

        return view('cm.liquidation.approve', $this->withSystemSettings([
            'liquidation' => $liquidation,
            'transaction' => $transaction,
            'step' => $step,
            'glAccounts' => $expenseAccounts, // Debit accounts
            'creditAccounts' => $creditAccounts, // Credit accounts
            'pendingElsewhere' => $pendingElsewhere,
            'availableBalance' => $availableBalance,
            'excessAmount' => $excessAmount,
        ]));
    }

    public function approveLiquidation(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_LIQ);

        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $liquidation = $transaction->approvable;
        abort_unless($liquidation instanceof CashAdvanceLiquidation, 404);

        DB::transaction(function () use ($request, $step, $liquidation) {
            $cashAdvance = $liquidation->cashAdvance;
            $newTotal = 0;
            $hasLinesData = $request->has('lines') && is_array($request->input('lines'));

            // Process line edits if step allows
            if ($hasLinesData && ($step->can_edit_chart_of_account || $step->can_edit_amount)) {
                foreach ($request->input('lines', []) as $lineId => $lineData) {
                    $detail = CashAdvanceLiquidationDetail::where('liquidation_id', $liquidation->id)->findOrFail($lineId);

                    if ($step->can_edit_chart_of_account && !empty($lineData['gl_account_id'])) {
                        $detail->gl_account_id = $lineData['gl_account_id'];
                    }

                    if ($step->can_edit_amount && isset($lineData['amount'])) {
                        $detail->amount = $lineData['amount'];
                    }

                    $detail->updated_by = Auth::id();
                    $detail->save();
                    $newTotal += (float) $detail->amount;
                }

                // Update liquidation total
                $liquidation->update(['total_expenses' => $newTotal, 'updated_by' => Auth::id()]);
                $liquidation->refresh();
            } else {
                $liquidation->refresh();
                // Verify total matches details sum
                $detailsSum = $liquidation->details()->sum('amount');
                if (abs($liquidation->total_expenses - $detailsSum) > 0.01) {
                    \Log::warning('Fixing liquidation total during approval (no edits)', [
                        'liquidation_id' => $liquidation->id,
                        'stored_total' => $liquidation->total_expenses,
                        'details_sum' => $detailsSum,
                    ]);
                    $liquidation->update(['total_expenses' => $detailsSum]);
                    $liquidation->refresh();
                }
            }

            // Handle credit account update if step allows GL account edit
            if ($step->can_edit_chart_of_account && $request->has('credit_account_id')) {
                $validated = $request->validate([
                    'credit_account_id' => 'nullable|exists:chart_of_accounts,id',
                ]);

                // Only save credit account if there's excess
                $availableBalance = (float) $cashAdvance->disbursed_amount - (float) $cashAdvance->liquidated_amount;
                $excess = max(0, $liquidation->total_expenses - $availableBalance);

                if ($excess > 0 && !empty($validated['credit_account_id'])) {
                    $liquidation->update([
                        'credit_account_id' => $validated['credit_account_id'],
                        'updated_by' => Auth::id(),
                    ]);
                } elseif ($excess <= 0) {
                    $liquidation->update(['credit_account_id' => null]);
                }
            }
        });

        // Proceed with the approval workflow
        $this->approvals->approve($transaction->fresh(), Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Liquidation approved.');
    }

    public function returnLiquidation(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_LIQ);

        $request->validate(['remarks' => 'required|string|max:1000']);
        $this->approvals->returnToRequester($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Liquidation returned to requester.');
    }

    public function rejectLiquidation(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_LIQ);

        $request->validate(['remarks' => 'required|string|max:1000']);
        $this->approvals->reject($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Liquidation rejected.');
    }

    // ==================== REFUND (Employee to Company) ====================
    public function refundsIndex(Request $request)
    {
        $this->authorizeRead(Modules::CM, Modules::CM_REF);

        $user = Auth::user();

        $refunds = CashAdvanceRefund::query()
            ->with(['cashAdvance', 'employee', 'creator', 'latestApprovalTransaction'])
            ->where('type', 'refund') // Only refunds
            ->when($user->role_id != 1, function ($q) use ($user) {
                return $q->where('employee_id', $user->id);
            })
            ->when($request->filled('searchstatus'), function ($q) use ($request) {
                return $q->where('approval_status', $request->searchstatus);
            })
            ->when($request->filled('searchca'), function ($q) use ($request) {
                return $q->where('cash_advance_id', $request->searchca);
            })
            ->orderByDesc('id')
            ->paginate(config('app.paginate', 15));

        return view('cm.refund.index', compact('refunds'));
    }

    public function createRefund(CashAdvance $cashAdvance)
    {
        $this->authorizeCreate(Modules::CM, Modules::CM_REF);

        if ($cashAdvance->employee_id != Auth::id()) {
            abort(403, 'You can only request a refund for your own Cash Advances.');
        }
        // Only allow refund if CA is approved and has a positive balance (excess)
        if ($cashAdvance->approval_status !== '2') {
            abort(403, 'This Cash Advance is not approved.');
        }

        //check for pending liquidations that might affect the excess amount
        $pendingLiquidationAmount = (float) CashAdvanceLiquidation::where('cash_advance_id', $cashAdvance->id)
            ->where('approval_status', '1') // pending
            ->sum('total_expenses');

        $pendingRefundAmount = (float) CashAdvanceRefund::where('cash_advance_id', $cashAdvance->id)
            ->where('approval_status', '1') // pending
            ->sum('amount');

        $excess = (float) $cashAdvance->disbursed_amount
            - (float) $cashAdvance->liquidated_amount
            - (float) $pendingLiquidationAmount
            - (float) $pendingRefundAmount;

        if ($excess <= 0) {
            abort(403, 'No excess amount to refund.');
        }

        // Check approver setup for refund
        if (!$this->hasApproverSetup('refund', $excess)) {
            return back()->with('error', 'No approver configured for Refunds. Please contact administrator.');
        }

        $allAccounts = $this->getGlAccounts();
        $assetAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'ASSET';
        })->values();

        return view('cm.refund.form', $this->withSystemSettings([
            'cashAdvance' => $cashAdvance,
            'refund' => new CashAdvanceRefund(),
            'isEdit' => false,
            'excessAmount' => $excess,
            'glAccounts' => $assetAccounts,
        ]));
    }

    public function storeRefund(Request $request)
    {
        $this->authorizeCreate(Modules::CM, Modules::CM_REF);

        $validated = $request->validate([
            'cash_advance_id' => 'required|exists:cash_advances,id',
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'required|string|max:500',
            'gl_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        $cashAdvance = CashAdvance::findOrFail($validated['cash_advance_id']);

        // Validate amount doesn't exceed excess
        $excess = (float) $cashAdvance->disbursed_amount - (float) $cashAdvance->liquidated_amount;
        if ($validated['amount'] > $excess) {
            return back()->withErrors(['amount' => "Amount ({$validated['amount']}) exceeds the excess amount ({$excess})."]);
        }

        if (!$this->hasApproverSetup('refund', $validated['amount'])) {
            return back()->with('error', 'No approver configured for Refunds. Please contact administrator.');
        }

        DB::transaction(function () use ($validated, $cashAdvance, $request) {
            $refund = CashAdvanceRefund::create([
                'organization_id' => $this->getOrganizationId(),
                'cash_advance_id' => $cashAdvance->id,
                'employee_id' => Auth::id(),
                'amount' => $validated['amount'],
                'purpose' => $validated['purpose'],
                'gl_account_id' => $validated['gl_account_id'],
                'type' => 'refund',
                'status' => '0',
                'approval_status' => '0',
                'created_by' => Auth::id(),
            ]);

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($refund, 'refund', (float) $validated['amount'], Auth::id());
                $refund->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('cm.ref')->with('success', 'Refund saved.');
    }

    public function editRefund(CashAdvanceRefund $refund)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REF);

        if ($refund->employee_id != Auth::id()) {
            abort(403, 'You can only edit your own refunds.');
        }
        if (!in_array($refund->approval_status, ['0', '4'])) {
            abort(403, 'Only draft or returned refunds can be edited.');
        }

        $allAccounts = $this->getGlAccounts();
        $assetAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'ASSET';
        })->values();

        return view('cm.refund.form', $this->withSystemSettings([
            'cashAdvance' => $refund->cashAdvance,
            'refund' => $refund,
            'isEdit' => true,
            'excessAmount' => 0,
            'glAccounts' => $assetAccounts,
        ]));
    }

    public function updateRefund(Request $request, CashAdvanceRefund $refund)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REF);

        if ($refund->employee_id != Auth::id()) {
            abort(403, 'You can only edit your own refunds.');
        }
        if (!in_array($refund->approval_status, ['0', '4'])) {
            abort(403, 'Only draft or returned refunds can be edited.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'required|string|max:500',
            'gl_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        $cashAdvance = $refund->cashAdvance;
        $excess = (float) $cashAdvance->disbursed_amount - (float) $cashAdvance->liquidated_amount;
        if ($validated['amount'] > $excess) {
            return back()->withErrors(['amount' => "Amount ({$validated['amount']}) exceeds the excess amount ({$excess})."]);
        }

        DB::transaction(function () use ($validated, $request, $refund) {
            $refund->update([
                'amount' => $validated['amount'],
                'purpose' => $validated['purpose'],
                'gl_account_id' => $validated['gl_account_id'],
                'updated_by' => Auth::id(),
            ]);

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($refund->fresh(), 'refund', (float) $validated['amount'], Auth::id());
                $refund->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('cm.ref')->with('success', 'Refund updated.');
    }

    public function showRefundApproval(CashAdvanceRefund $refund, approval_transaction $transaction)
    {
        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $allAccounts = $this->getGlAccounts();
        $assetAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'ASSET';
        })->values();

        return view('cm.refund.approve', $this->withSystemSettings([
            'refund' => $refund,
            'transaction' => $transaction,
            'step' => $step,
            'glAccounts' => $assetAccounts,
        ]));
    }

    public function returnRefund(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REF);

        $request->validate([
            'remarks' => 'required|string|min:1|max:1000'
        ]);

        $this->approvals->returnToRequester($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Refund returned to requester.');
    }

    public function rejectRefund(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REF);

        $request->validate([
            'remarks' => 'required|string|min:1|max:1000'
        ]);

        $this->approvals->reject($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Refund rejected.');
    }

    public function approveRefund(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REF);

        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $refund = $transaction->approvable;
        abort_unless($refund instanceof CashAdvanceRefund, 404);

        // If step allows editing, validate and update
        if ($step->can_edit_chart_of_account || $step->can_edit_amount) {
            $rules = [];

            if ($step->can_edit_chart_of_account) {
                $rules['gl_account_id'] = 'required|exists:chart_of_accounts,id';
            }

            if ($step->can_edit_amount) {
                $rules['amount'] = 'required|numeric|min:0.01';
            }

            $validated = $request->validate($rules);

            // Prepare update data
            $updateData = ['updated_by' => Auth::id()];

            if ($step->can_edit_chart_of_account && isset($validated['gl_account_id'])) {
                $updateData['gl_account_id'] = $validated['gl_account_id'];
            }

            if ($step->can_edit_amount && isset($validated['amount'])) {
                // Validate amount doesn't exceed CA balance
                $ca = $refund->cashAdvance;
                $maxRefund = (float) $ca->amount - (float) $ca->liquidated_amount;
                if ($validated['amount'] > $maxRefund) {
                    throw ValidationException::withMessages([
                        'amount' => "Refund amount ({$validated['amount']}) exceeds the available balance ({$maxRefund})."
                    ]);
                }
                $updateData['amount'] = $validated['amount'];
            }

            $refund->update($updateData);
            $refund->refresh();
        }

        // Proceed with the approval workflow
        $this->approvals->approve($transaction->fresh(), Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Refund approved.');
    }

    // ==================== REIMBURSEMENT (Company to Employee) ====================
    public function reimbursementsIndex(Request $request)
    {
        $this->authorizeRead(Modules::CM, Modules::CM_REIM);

        $user = Auth::user();

        $reimbursements = CashAdvanceRefund::query()
            ->with(['employee', 'creator', 'details.glAccount', 'latestApprovalTransaction', 'cashAdvance', 'liquidation'])
            ->where('type', 'reimbursement')
            ->when($user->role_id != 1, function ($q) use ($user) {
                return $q->where('employee_id', $user->id);
            })
            ->when($request->filled('searchstatus'), function ($q) use ($request) {
                return $q->where('approval_status', $request->searchstatus);
            })
            ->when($request->filled('searchca'), function ($q) use ($request) {
                return $q->where('cash_advance_id', $request->searchca);
            })
            ->orderByDesc('id')
            ->paginate(config('app.paginate', 15));

        // Calculate stats
        $totalReimbursements = $reimbursements->total();
        $pendingApproval = CashAdvanceRefund::where('type', 'reimbursement')
            ->where('approval_status', '1')
            ->count();
        $draftCount = CashAdvanceRefund::where('type', 'reimbursement')
            ->where('approval_status', '0')
            ->whereNotNull('liquidation_id')
            ->count();
        $totalAmount = CashAdvanceRefund::where('type', 'reimbursement')
            ->where('approval_status', '2')
            ->sum('amount');
        $rejectedCount = CashAdvanceRefund::where('type', 'reimbursement')
            ->where('approval_status', '3')
            ->count();
        $postedCount = CashAdvanceRefund::where('type', 'reimbursement')
            ->where('approval_status', '5')
            ->count();

        return view('cm.reimbursement.index', compact(
            'reimbursements',
            'totalReimbursements',
            'pendingApproval',
            'draftCount',
            'totalAmount',
            'rejectedCount',
            'postedCount',
        ));
    }

    /**
     * Submit a draft reimbursement (from excess liquidation) for approval
     */
    public function submitReimbursement(CashAdvanceRefund $reimbursement)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REIM);

        if ($reimbursement->type !== 'reimbursement') {
            abort(404);
        }

        if (!in_array($reimbursement->approval_status, ['0', '4'])) {
            return back()->with('error', 'This reimbursement has already been submitted.');
        }

        if ($reimbursement->employee_id != Auth::id()) {
            abort(403, 'You can only submit your own reimbursements.');
        }

        // Check if approver is set up
        if (!$this->hasApproverSetup('reimbursement', (float) $reimbursement->amount)) {
            return back()->with('error', 'No approver configured for Reimbursements. Please contact administrator.');
        }

        // Submit for approval
        $this->approvals->submit($reimbursement, 'reimbursement', (float) $reimbursement->amount, Auth::id());
        $reimbursement->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);

        return redirect()->route('cm.reim')->with('success', 'Reimbursement submitted for approval.');
    }

    public function createReimbursement()
    {
        $this->authorizeCreate(Modules::CM, Modules::CM_REIM);

        // Check approver setup for reimbursement
        if (!$this->hasApproverSetup('reimbursement', 0)) {
            return back()->with('error', 'No approver configured for Reimbursements. Please contact administrator.');
        }

        return view('cm.reimbursement.form', $this->withSystemSettings([
            'glAccounts' => $this->getGlAccounts(),
        ]));
    }

    public function storeReimbursement(Request $request)
    {
        $this->authorizeCreate(Modules::CM, Modules::CM_REIM);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'required|string|max:500',
            'details' => 'required|array|min:1',
            'details.*.expense_date' => 'required|date',
            'details.*.description' => 'required|string|max:255',
            'details.*.gl_account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.amount' => 'required|numeric|min:0.01',
        ]);

        $totalAmount = collect($validated['details'])->sum('amount');

        if ($totalAmount != $validated['amount']) {
            return back()->withErrors(['amount' => 'Total details amount must equal the reimbursement amount.']);
        }

        // Check approver setup
        if (!$this->hasApproverSetup('reimbursement', $totalAmount)) {
            return back()->with('error', 'No approver configured for Reimbursements. Please contact administrator.');
        }

        DB::transaction(function () use ($validated, $totalAmount) {
            $reimbursement = CashAdvanceRefund::create([
                'organization_id' => $this->getOrganizationId(),
                'employee_id' => Auth::id(),
                'amount' => $totalAmount,
                'purpose' => $validated['purpose'],
                'type' => 'reimbursement', // company to employee
                'status' => '0',
                'approval_status' => '0',
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['details'] as $detail) {
                ReimbursementDetail::create([
                    'reimbursement_id' => $reimbursement->id,
                    'expense_date' => $detail['expense_date'],
                    'description' => $detail['description'],
                    'gl_account_id' => $detail['gl_account_id'],
                    'amount' => $detail['amount'],
                    'created_by' => Auth::id(),
                ]);
            }

            $this->approvals->submit($reimbursement, 'reimbursement', (float) $totalAmount, Auth::id());
        });

        return redirect()->route('cm.reimbursements.index')->with('success', 'Reimbursement submitted for approval.');
    }

    public function showReimbursementApproval(CashAdvanceRefund $reimbursement, approval_transaction $transaction)
    {
        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $reimbursement->load(['details.glAccount', 'employee', 'cashAdvance']);

        $allAccounts = $this->getGlAccounts();

        // Expense accounts for debit (existing details)
        $expenseAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && in_array($account->accountType->code, ['EXPENSE', 'COST']);
        })->values();

        // Liability accounts for credit (Reimbursement Payable)
        $creditAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'LIABILITY';
        })->values();

        return view('cm.reimbursement.approve', $this->withSystemSettings([
            'reimbursement' => $reimbursement,
            'transaction' => $transaction,
            'step' => $step,
            'expenseAccounts' => $expenseAccounts,
            'creditAccounts' => $creditAccounts,
        ]));
    }

    public function approveReimbursement(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REIM);

        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $reimbursement = $transaction->approvable;
        abort_unless($reimbursement instanceof CashAdvanceRefund, 404);

        // If step allows editing, validate and update
        if ($step->can_edit_chart_of_account || $step->can_edit_amount) {
            $rules = [];

            // For line item edits (debit accounts)
            if ($step->can_edit_chart_of_account && $request->has('lines')) {
                foreach ($request->input('lines', []) as $lineId => $lineData) {
                    if (!empty($lineData['gl_account_id'])) {
                        $rules["lines.{$lineId}.gl_account_id"] = 'exists:chart_of_accounts,id';
                    }
                    if (isset($lineData['amount'])) {
                        $rules["lines.{$lineId}.amount"] = 'numeric|min:0.01';
                    }
                }
            }

            // Credit account (reimbursement payable)
            if ($step->can_edit_chart_of_account) {
                $rules['credit_account_id'] = 'nullable|exists:chart_of_accounts,id';
            }

            // Total amount
            if ($step->can_edit_amount) {
                $rules['amount'] = 'nullable|numeric|min:0.01';
            }

            $validated = $request->validate($rules);

            // Prepare update data
            $updateData = ['updated_by' => Auth::id()];

            // Update debit GL accounts for each line
            if ($step->can_edit_chart_of_account && $request->has('lines')) {
                foreach ($request->input('lines', []) as $lineId => $lineData) {
                    $detail = ReimbursementDetail::where('reimbursement_id', $reimbursement->id)->findOrFail($lineId);

                    if (!empty($lineData['gl_account_id'])) {
                        $detail->gl_account_id = $lineData['gl_account_id'];
                    }

                    if ($step->can_edit_amount && isset($lineData['amount'])) {
                        $detail->amount = $lineData['amount'];
                    }

                    $detail->updated_by = Auth::id();
                    $detail->save();
                }

                // Recalculate total from updated lines
                $newTotal = $reimbursement->details()->sum('amount');
                $updateData['amount'] = $newTotal;
            } elseif ($step->can_edit_amount && isset($validated['amount'])) {
                $updateData['amount'] = $validated['amount'];
            }

            // Update credit account (reimbursement payable)
            if ($step->can_edit_chart_of_account && isset($validated['credit_account_id'])) {
                $updateData['gl_account_id'] = $validated['credit_account_id'];
            }

            $reimbursement->update($updateData);
            $reimbursement->refresh();
        }

        // Proceed with the approval workflow
        $this->approvals->approve($transaction->fresh(), Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Reimbursement approved.');
    }

    public function returnReimbursement(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REIM);

        $request->validate([
            'remarks' => 'required|string|min:1|max:1000'
        ]);

        $this->approvals->returnToRequester($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Reimbursement returned to requester.');
    }

    public function rejectReimbursement(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::CM, Modules::CM_REIM);

        $request->validate([
            'remarks' => 'required|string|min:1|max:1000'
        ]);

        $this->approvals->reject($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Reimbursement rejected.');
    }


    // ==================== HELPERS ====================

    protected function getGlAccounts()
    {
        return chart_of_account::with('structure', 'accountType')
            ->where('organization_id', $this->getOrganizationId())
            ->whereHas('structure', fn($q) => $q->where('status', 1)->where('is_default', true))
            ->where('is_posting', true)
            ->where('status', 1)
            ->orderBy('account_code')
            ->get();
    }

    public function showApproval(CashAdvance $cashAdvance, approval_transaction $transaction)
    {
        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $allAccounts = $this->getGlAccounts();
        $assetAccounts = $allAccounts->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'ASSET';
        })->values();

        return view('cm.ca.approve', $this->withSystemSettings([
            'cashAdvance' => $cashAdvance,
            'transaction' => $transaction,
            'step' => $step,
            'glAccounts' => $assetAccounts,
        ]));
    }

    /**
     * Process the cash advance approval with potential GL account updates
     */
    public function approve(Request $request, approval_transaction $transaction)
    {
        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $cashAdvance = $transaction->approvable;
        // dd($step, $cashAdvance);
        // If step allows editing GL account, validate and update
        if ($step->can_edit_chart_of_account) {
            $validated = $request->validate([
                'gl_account_id' => 'required|exists:chart_of_accounts,id',
            ]);

            // Update the GL account
            $cashAdvance->update([
                'gl_account_id' => $validated['gl_account_id'],
                'updated_by' => Auth::id(),
            ]);
        }

        // Proceed with the approval workflow
        $this->approvals->approve($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Cash Advance approved.');
    }


    public function returnToRequester(Request $request, approval_transaction $transaction)
    {
        $request->validate(['remarks' => 'required|string|max:1000']);
        $this->approvals->returnToRequester($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Cash Advance returned to requester.');
    }

    /**
     * Reject cash advance
     */
    public function reject(Request $request, approval_transaction $transaction)
    {
        $request->validate(['remarks' => 'required|string|max:1000']);
        $this->approvals->reject($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Cash Advance rejected.');
    }

}