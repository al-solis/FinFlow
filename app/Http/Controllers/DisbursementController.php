<?php

namespace App\Http\Controllers;

use App\Models\rfd_header;
use App\Models\bank_account;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettings;
use App\Models\payment_method;
use App\Models\CashAdvance;
use App\Models\CashAdvanceRefund;
use RuntimeException;

class DisbursementController extends Controller
{
    protected function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function __construct(protected AccountingService $accounting)
    {
    }

    public function index()
    {
        // RFD Stats
        $rfdPendingApproval = rfd_header::where('approval_status', '1')->count();
        $rfdAwaitingDisbursement = rfd_header::where('approval_status', '2')
            ->whereIn('payment_status', [0, 2])
            ->count();

        // Cash Advance Stats
        $caPendingApproval = CashAdvance::where('approval_status', '1')->count();
        $caAwaitingDisbursement = CashAdvance::where('approval_status', '2')
            ->whereColumn('disbursed_amount', '<', 'amount')
            ->count();

        // Reimbursement Stats
        $reimbursementPendingApproval = CashAdvanceRefund::where('type', 'reimbursement')
            ->where('approval_status', '1')
            ->count();
        $reimbursementAwaitingDisbursement = CashAdvanceRefund::where('type', 'reimbursement')
            ->where('approval_status', '2')
            ->where('status', '!=', '5')
            ->count();

        $bankAccounts = bank_account::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $paymentMethods = payment_method::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        // Get RFDs ready for disbursement
        $rfds = rfd_header::with(['details', 'apInvoices', 'currency', 'creator'])
            ->where('approval_status', '2')
            ->whereIn('payment_status', [0, 2])
            ->orderByDesc('id')
            ->paginate(config('app.paginate', 15));

        // Get Cash Advances ready for disbursement
        $cashAdvances = CashAdvance::with(['employee', 'glAccount'])
            ->where('approval_status', '2')
            ->whereColumn('disbursed_amount', '<', 'amount')
            ->orderByDesc('id')
            ->get();

        // Get Reimbursements ready for disbursement
        $reimbursements = CashAdvanceRefund::with(['employee', 'cashAdvance'])
            ->where('type', 'reimbursement')
            ->where('approval_status', '2')
            ->where('status', '!=', '5')
            ->orderByDesc('id')
            ->get();

        $pendingApproval = $rfdPendingApproval + $caPendingApproval + $reimbursementPendingApproval;
        $awaitingDisbursement = $rfdAwaitingDisbursement + $caAwaitingDisbursement + $reimbursementAwaitingDisbursement;

        return view('cm.disbursement.index', compact(
            'rfds',
            'cashAdvances',
            'reimbursements',
            'pendingApproval',
            'awaitingDisbursement',
            'bankAccounts',
            'paymentMethods'
        ));
    }

    /**
     * Show RFD disbursement details
     */
    public function show(rfd_header $rfd)
    {
        $rfd->load(['details.glAccount', 'apInvoices' => fn($q) => $q->where('status', '!=', 'paid'), 'currency']);

        $bankAccounts = bank_account::where('organization_id', $rfd->organization_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $paymentMethods = payment_method::where('organization_id', $rfd->organization_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('cm.disbursement.rfd_show', compact('rfd', 'bankAccounts', 'paymentMethods'));
    }

    /**
     * Show Cash Advance disbursement details
     */
    public function showCa(CashAdvance $cashAdvance)
    {
        $cashAdvance->load(['employee', 'glAccount']);

        $bankAccounts = bank_account::where('organization_id', $cashAdvance->organization_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $paymentMethods = payment_method::where('organization_id', $cashAdvance->organization_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $remainingAmount = $cashAdvance->remaining_amount;

        return view('cm.disbursement.ca_show', compact(
            'cashAdvance',
            'remainingAmount',
            'bankAccounts',
            'paymentMethods'
        ));
    }

    /**
     * Show Reimbursement disbursement details
     */
    public function showReimbursement(CashAdvanceRefund $reimbursement)
    {
        $reimbursement->load(['employee', 'cashAdvance', 'glAccount']);

        $bankAccounts = bank_account::where('organization_id', $reimbursement->organization_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $paymentMethods = payment_method::where('organization_id', $reimbursement->organization_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('cm.disbursement.reimbursement_show', compact(
            'reimbursement',
            'bankAccounts',
            'paymentMethods'
        ));
    }

    /**
     * Disburse RFD
     */
    public function disburse(Request $request, rfd_header $rfd)
    {
        $data = $request->validate([
            'ap_invoice_ids' => 'required|array|min:1',
            'ap_invoice_ids.*' => 'exists:ap_invoices,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount_to_pay' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:100',
            'check_date' => 'nullable|date',
        ]);

        try {
            $this->accounting->postDisbursement(
                apInvoiceIds: $data['ap_invoice_ids'],
                bankAccountId: (int) $data['bank_account_id'],
                paymentMethodId: (int) $data['payment_method_id'],
                amountToPay: (float) $data['amount_to_pay'],
                referenceNumber: $data['reference_number'] ?? '',
                checkDate: $data['check_date'] ?? null,
                actorId: Auth::id(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['disbursement' => $e->getMessage()]);
        }

        return redirect()->route('cm.dv')->with('success', 'RFD Disbursement recorded.');
    }

    /**
     * Disburse Cash Advance
     */
    public function disburseCa(Request $request, CashAdvance $cashAdvance)
    {
        $data = $request->validate([
            'amount_to_pay' => 'required|numeric|min:0.01|max:' . $cashAdvance->remaining_amount,
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number' => 'nullable|string|max:100',
            'check_date' => 'nullable|date',
        ]);

        try {
            $this->accounting->disburseCashAdvance(
                ca: $cashAdvance,
                bankAccountId: (int) $data['bank_account_id'],
                paymentMethodId: (int) $data['payment_method_id'],
                amountToPay: (float) $data['amount_to_pay'],
                referenceNumber: $data['reference_number'] ?? '',
                checkDate: $data['check_date'] ?? null,
                actorId: Auth::id(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['disbursement' => $e->getMessage()]);
        }

        return redirect()->route('cm.dv')->with('success', 'Cash Advance disbursement recorded.');
    }

    /**
     * Disburse Reimbursement
     */
    public function disburseReimbursement(Request $request, CashAdvanceRefund $reimbursement)
    {
        $data = $request->validate([
            'amount_to_pay' => 'required|numeric|min:0.01|max:' . $reimbursement->amount,
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number' => 'nullable|string|max:100',
            'check_date' => 'nullable|date',
        ]);

        try {
            $this->accounting->disburseReimbursement(
                reimbursement: $reimbursement,
                bankAccountId: (int) $data['bank_account_id'],
                paymentMethodId: (int) $data['payment_method_id'],
                amountToPay: (float) $data['amount_to_pay'],
                referenceNumber: $data['reference_number'] ?? '',
                checkDate: $data['check_date'] ?? null,
                actorId: Auth::id(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['disbursement' => $e->getMessage()]);
        }

        return redirect()->route('cm.dv')->with('success', 'Reimbursement disbursement recorded.');
    }
}