<?php

namespace App\Http\Controllers;

use App\Models\rfd_header;
use App\Models\bank_account;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettings;
use App\Models\payment_method;

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
        $pendingApproval = rfd_header::where('approval_status', '1')->count();
        $awaitingDisbursement = rfd_header::where('approval_status', '2')
            ->whereIn('payment_status', [0, 2])
            ->count();

        $bankAccounts = bank_account::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $paymentMethods = payment_method::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $rfds = rfd_header::with(['details', 'apInvoices', 'currency'])
            ->where('approval_status', '2')
            ->orderByDesc('approved_by')
            ->paginate(config('app.paginate', 15));

        return view('cm.disbursement.index', compact('rfds', 'pendingApproval', 'awaitingDisbursement', 'bankAccounts', 'paymentMethods'));
    }

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

        return view('cash-management.disbursement.index', compact('rfd', 'bankAccounts', 'paymentMethods'));
        // (adjust to however you're already passing $rfds/$paymentMethods to the index view listed above)
    }

    public function disburse(Request $request, rfd_header $rfd)
    {
        $data = $request->validate([
            'ap_invoice_ids' => 'required|array|min:1',
            'ap_invoice_ids.*' => 'exists:ap_invoices,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number' => 'nullable|string|max:100',
            'check_date' => 'nullable|date',
        ]);

        $this->accounting->postDisbursement(
            apInvoiceIds: $data['ap_invoice_ids'],
            bankAccountId: (int) $data['bank_account_id'],
            paymentMethodId: (int) $data['payment_method_id'],
            referenceNumber: $data['reference_number'] ?? '',
            checkDate: $data['check_date'] ?? null,
            actorId: Auth::id(),
        );

        return redirect()->route('cm.dv')->with('success', 'Disbursement recorded.');
    }
}