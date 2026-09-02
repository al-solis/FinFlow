<?php

namespace App\Http\Controllers;

use App\Models\gl_journal_line;
use Illuminate\Http\Request;
use App\Models\chart_of_account;

class TrialBalanceController extends Controller
{
    protected const DEBIT_NORMAL = ['ASSET', 'EXPENSE'];

    // public function index(Request $request)
    // {
    //     $from = $request->input('from', now()->startOfMonth()->toDateString());
    //     $to = $request->input('to', now()->toDateString());

    //     $rows = gl_journal_line::query()
    //         ->join('gl_journals', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
    //         ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'gl_journal_lines.gl_account_id')
    //         ->join('account_types', 'account_types.id', '=', 'chart_of_accounts.account_type_id')
    //         ->where('gl_journals.status', 'posted')
    //         ->whereBetween('gl_journals.journal_date', [$from, $to])
    //         ->selectRaw('chart_of_accounts.id as account_id, chart_of_accounts.account_code, chart_of_accounts.account_name,
    //                      account_types.code as account_type_code,
    //                      SUM(gl_journal_lines.debit) as total_debit, SUM(gl_journal_lines.credit) as total_credit')
    //         ->groupBy('chart_of_accounts.id', 'chart_of_accounts.account_code', 'chart_of_accounts.account_name', 'account_types.code')
    //         ->orderBy('chart_of_accounts.account_code')
    //         ->get()
    //         ->map(function ($row) {
    //             $isDebitNormal = in_array($row->account_type_code, self::DEBIT_NORMAL, true);
    //             $net = $row->total_debit - $row->total_credit;
    //             $row->balance = $isDebitNormal ? $net : -$net;
    //             $row->balance_side = $isDebitNormal ? 'debit' : 'credit';
    //             return $row;
    //         });

    //     $totalDebit = $rows->sum('total_debit');
    //     $totalCredit = $rows->sum('total_credit');

    //     return view('accounting.trial-balance', compact('rows', 'from', 'to', 'totalDebit', 'totalCredit'));
    // }
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $rows = gl_journal_line::query()
            ->join('gl_journals', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'gl_journal_lines.gl_account_id')
            ->join('account_types', 'account_types.id', '=', 'chart_of_accounts.account_type_id')
            ->where('gl_journals.status', 'posted')
            ->whereBetween('gl_journals.journal_date', [$from, $to])
            ->selectRaw('chart_of_accounts.id as account_id, chart_of_accounts.account_code, chart_of_accounts.account_name,
                         account_types.code as account_type_code,
                         SUM(gl_journal_lines.debit) as total_debit, SUM(gl_journal_lines.credit) as total_credit')
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.account_code', 'chart_of_accounts.account_name', 'account_types.code')
            ->orderBy('chart_of_accounts.account_code')
            ->get()
            ->map(function ($row) {
                $account = chart_of_account::find($row->account_id);
                $row->getFormattedAccountCodeAttribute = $account ? $account->getFormattedAccountCodeAttribute() : $row->account_code;
                $net = (float) $row->total_debit - (float) $row->total_credit;
                $row->debit_balance = $net > 0 ? $net : 0;
                $row->credit_balance = $net < 0 ? -$net : 0;
                return $row;
            });

        $totalDebit = $rows->sum('debit_balance');
        $totalCredit = $rows->sum('credit_balance');
        $isBalanced = round($totalDebit - $totalCredit, 2) === 0.0;

        return view('gl.trial-balance', compact('rows', 'from', 'to', 'totalDebit', 'totalCredit', 'isBalanced'));
    }
}