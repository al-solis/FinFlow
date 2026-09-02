<?php

namespace App\Http\Controllers;

use App\Models\approval_transaction;
use App\Models\rfd_header;
use App\Models\CashAdvance;
use App\Models\CashAdvanceLiquidation;
use App\Models\CashAdvanceRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
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
        $rfdCount = rfd_header::count();
        $caCount = CashAdvance::count();
        $liquidationCount = CashAdvanceLiquidation::count();
        $refundCount = CashAdvanceRefund::count();

        return $rfdCount + $caCount + $liquidationCount + $refundCount;
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
        ];

        return $map[$type] ?? 'bg-gray-50 text-gray-700';
    }
}