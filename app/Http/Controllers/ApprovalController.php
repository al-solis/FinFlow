<?php

namespace App\Http\Controllers;

use App\Models\approval_transaction;
use App\Models\rfd_header;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $pendingApproval = approval_transaction::pendingFor($user)->count();

        $awaitingDisbursement = rfd_header::where('approval_status', '2')
            ->whereIn('payment_status', [0, 2])
            ->count();

        $totalRequests = rfd_header::count(); // extend with unionAll across modules as more get added

        $transactions = approval_transaction::pendingFor($user)
            ->with([
                'workflow',
                'approvable' => function ($morphTo) {
                    $morphTo->morphWith([
                        rfd_header::class => ['details', 'creator', 'currency'],
                    ]);
                }
            ])
            ->orderByDesc('approval_transactions.created_at')
            ->paginate(config('app.paginate', 15));

        return view('approvals.index', compact('transactions', 'pendingApproval', 'awaitingDisbursement', 'totalRequests'));
    }
}