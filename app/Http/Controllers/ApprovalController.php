<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Services\AuthorizationService;
use App\Services\SystemSettings;
use App\Traits\AuthorizesAccessRights;
use App\Traits\WithSystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\approval_transaction;
use App\Models\rfd_header;
use App\Models\CashAdvance;
use App\Models\CashAdvanceLiquidation;
use App\Models\CashAdvanceRefund;
use App\Models\gl_journal;
use App\Models\sales_invoice;
use App\Models\collection;
use App\Models\credit_memo;
use App\Models\debit_memo;


class ApprovalController extends Controller
{
    use AuthorizesAccessRights;
    use WithSystemSettings;

    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }


    public function index(Request $request)
    {
        // $this->authorizeRead(Modules::APPW, Modules::APPW_APPR);
        $user = Auth::user();

        // Get pending approval count for the user
        $pendingApproval = approval_transaction::pendingFor($user)->count();

        // Get awaiting disbursement count (RFDs that are approved but not fully paid)
        $awaitingDisbursement = rfd_header::where('approval_status', '2')
            ->whereIn('payment_status', [0, 2])
            ->count();

        // Get total requests across ALL modules
        $totalRequests = $this->getTotalRequests();

        // Get pending transactions with polymorphic relationships
        $transactions = approval_transaction::pendingFor($user)
            ->with([
                'workflow',
                'approvable' => function ($morphTo) {
                    $morphTo->morphWith([
                        rfd_header::class => ['details', 'creator', 'currency'],
                        CashAdvance::class => ['employee', 'creator'],
                        CashAdvanceLiquidation::class => ['cashAdvance', 'employee', 'creator'],
                        CashAdvanceRefund::class => ['employee', 'creator'],
                        gl_journal::class => ['creator'],
                        sales_invoice::class => ['creator'],
                        collection::class => ['customer', 'creator'],
                        credit_memo::class => ['creator', 'customer'],
                        debit_memo::class => ['creator', 'customer'],
                    ]);
                }
            ])
            ->orderByDesc('approval_transactions.created_at')
            ->paginate(config('app.paginate', 15));

        return view('approvals.index', compact(
            'transactions',
            'pendingApproval',
            'awaitingDisbursement',
            'totalRequests'
        ));
    }

    /**
     * Get total requests across all modules
     */
    private function getTotalRequests(): int
    {
        return rfd_header::count()
            + CashAdvance::count()
            + CashAdvanceLiquidation::count()
            + CashAdvanceRefund::count()
            + gl_journal::count()
            + sales_invoice::count()
            + collection::count()
            + credit_memo::count()
            + debit_memo::count();
    }

    /**
     * Get module type label for display
     */
    public static function getModuleLabel(string $type): string
    {
        $map = [
            rfd_header::class => 'RFD',
            CashAdvance::class => 'Cash Advance',
            CashAdvanceLiquidation::class => 'Liquidation',
            CashAdvanceRefund::class => 'Refund',
            gl_journal::class => 'Journal Entry',
            sales_invoice::class => 'Sales Invoice',
            collection::class => 'Collection',
            credit_memo::class => 'Credit Memo',
            debit_memo::class => 'Debit Memo',
        ];

        return $map[$type] ?? 'Unknown';
    }

    /**
     * Get module badge color for display
     */
    public static function getModuleBadgeClass(string $type): string
    {
        $map = [
            rfd_header::class => 'bg-blue-50 text-blue-700',
            CashAdvance::class => 'bg-purple-50 text-purple-700',
            CashAdvanceLiquidation::class => 'bg-green-50 text-green-700',
            CashAdvanceRefund::class => 'bg-orange-50 text-orange-700',
            gl_journal::class => 'bg-indigo-50 text-indigo-700',
            sales_invoice::class => 'bg-cyan-50 text-cyan-700',
            collection::class => 'bg-emerald-50 text-emerald-700',
            credit_memo::class => 'bg-rose-50 text-rose-700',
            debit_memo::class => 'bg-amber-50 text-amber-700',
        ];

        return $map[$type] ?? 'bg-gray-50 text-gray-700';
    }
}