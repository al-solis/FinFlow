<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\bank_account;
use App\Models\payment_method;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettings;
use RuntimeException;

class CashAdvanceDisbursementController extends Controller
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
        // Get approved CAs that need disbursement
        $cashAdvances = CashAdvance::with(['employee', 'glAccount'])
            ->where('approval_status', '2')
            ->where(function ($q) {
                $q->where('status', '2') // approved but not yet disbursed
                    ->orWhere('status', '5'); // partially disbursed
            })
            ->whereColumn('disbursed_amount', '<', 'amount')
            ->orderByDesc('id')
            ->paginate(config('app.paginate', 15));

        $pendingApproval = CashAdvance::where('approval_status', '1')->count();
        $awaitingDisbursement = CashAdvance::where('approval_status', '2')
            ->whereColumn('disbursed_amount', '<', 'amount')
            ->count();

        $bankAccounts = bank_account::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $paymentMethods = payment_method::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('cm.ca.disbursement', compact(
            'cashAdvances',
            'pendingApproval',
            'awaitingDisbursement',
            'bankAccounts',
            'paymentMethods'
        ));
    }

    public function disburse(Request $request, CashAdvance $cashAdvance)
    {
        $data = $request->validate([
            'amount_to_pay' => 'required|numeric|min:0.01',
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

        return redirect()->route('cm.ca.disbursement')->with('success', 'Cash Advance disbursed successfully.');
    }

    public function show(CashAdvance $cashAdvance)
    {
        $cashAdvance->load(['employee', 'glAccount']);

        $bankAccounts = bank_account::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $paymentMethods = payment_method::where('organization_id', $this->getOrganizationId())
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $remainingAmount = $cashAdvance->remaining_amount;

        return view('cm.ca.disbursement_show', compact(
            'cashAdvance',
            'remainingAmount',
            'bankAccounts',
            'paymentMethods'
        ));
    }
}