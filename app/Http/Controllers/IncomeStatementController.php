<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Services\AuthorizationService;
use App\Services\SystemSettings;
use App\Models\gl_journal;
use App\Models\chart_of_account;
use App\Models\account_type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncomeStatementController extends Controller
{
    use \App\Traits\AuthorizesAccessRights;
    use \App\Traits\WithSystemSettings;

    protected function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function index(Request $request)
    {
        $this->authorizeRead(Modules::FIN, Modules::FIN_IS);

        $currencySymbol = SystemSettings::getCurrencySymbol();

        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $view = $request->input('view', 'summary'); // summary, detailed, comparative

        $data = $this->generateIncomeStatement($from, $to);

        return view('fin.income_statement', array_merge($data, [
            'from' => $from,
            'to' => $to,
            'view' => $view,
            'currencySymbol' => $currencySymbol,
        ]));
    }

    public function generateIncomeStatement(string $from, string $to)
    {
        $organizationId = $this->getOrganizationId();

        // Get all revenue accounts
        $revenueAccounts = chart_of_account::where('organization_id', $organizationId)
            ->where('is_posting', true)
            ->whereHas('accountType', function ($q) {
                $q->whereIn('code', ['REVENUE', 'INCOME']);
            })
            ->orderBy('account_code')
            ->get();

        // Get all expense accounts
        $expenseAccounts = chart_of_account::where('organization_id', $organizationId)
            ->where('is_posting', true)
            ->whereHas('accountType', function ($q) {
                $q->whereIn('code', ['EXPENSE', 'COST']);
            })
            ->orderBy('account_code')
            ->get();

        // Get balances for revenue accounts
        $revenueBalances = $this->getAccountBalances($revenueAccounts->pluck('id')->toArray(), $from, $to);
        $expenseBalances = $this->getAccountBalances($expenseAccounts->pluck('id')->toArray(), $from, $to);

        // Map balances to accounts
        $revenue = $revenueAccounts->map(function ($account) use ($revenueBalances) {
            $account->balance = $revenueBalances[$account->id] ?? 0;
            return $account;
        });

        $expenses = $expenseAccounts->map(function ($account) use ($expenseBalances) {
            $account->balance = $expenseBalances[$account->id] ?? 0;
            return $account;
        });

        $totalRevenue = $revenue->sum('balance');
        $totalExpenses = $expenses->sum('balance');

        // Group revenue by category
        $revenueByCategory = $revenue->groupBy(function ($account) {
            return $account->accountCategory->description ?? 'Other';
        });

        // Group expenses by category
        $expensesByCategory = $expenses->groupBy(function ($account) {
            return $account->accountCategory->description ?? 'Other';
        });

        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'revenueByCategory' => $revenueByCategory,
            'expensesByCategory' => $expensesByCategory,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netIncome' => $netIncome,
        ];
    }

    protected function getAccountBalances(array $accountIds, string $from, string $to)
    {
        if (empty($accountIds)) {
            return [];
        }

        $organizationId = $this->getOrganizationId();

        $results = gl_journal::where('organization_id', $organizationId)
            ->where('status', 'posted')
            ->whereBetween('journal_date', [$from, $to])
            ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->whereIn('gl_journal_lines.gl_account_id', $accountIds)
            ->selectRaw('
                gl_journal_lines.gl_account_id as account_id,
                SUM(gl_journal_lines.debit) as total_debit,
                SUM(gl_journal_lines.credit) as total_credit
            ')
            ->groupBy('gl_journal_lines.gl_account_id')
            ->get();

        $balances = [];
        foreach ($results as $result) {
            $balances[$result->account_id] = (float) $result->total_debit - (float) $result->total_credit;
        }

        return $balances;
    }
}