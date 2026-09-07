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

class BalanceSheetController extends Controller
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
        $this->authorizeRead(Modules::FIN, Modules::FIN_BS);

        $asOf = $request->input('as_of', now()->toDateString());
        $view = $request->input('view', 'summary'); // summary, detailed

        $data = $this->generateBalanceSheet($asOf);

        return view('fin.balance_sheet', array_merge($data, [
            'asOf' => $asOf,
            'view' => $view,
        ]));
    }

    public function generateBalanceSheet(string $asOf)
    {
        $organizationId = $this->getOrganizationId();

        // Get all accounts with balances as of the date
        $accounts = chart_of_account::where('organization_id', $organizationId)
            ->where('is_posting', true)
            ->with(['accountType', 'accountCategory', 'accountSubcategory'])
            ->orderBy('account_code')
            ->get();

        $accountIds = $accounts->pluck('id')->toArray();
        $balances = $this->getAccountBalances($accountIds, $asOf);

        // Map balances to accounts
        $accountsWithBalance = $accounts->map(function ($account) use ($balances) {
            $account->balance = $balances[$account->id] ?? 0;
            return $account;
        });

        // Group by account type
        $assets = $accountsWithBalance->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'ASSET';
        })->values();

        $liabilities = $accountsWithBalance->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'LIABILITY';
        })->values();

        $equity = $accountsWithBalance->filter(function ($account) {
            return $account->accountType && $account->accountType->code === 'EQUITY';
        })->values();

        // Group by category within each type
        $assetsByCategory = $assets->groupBy(function ($account) {
            return $account->accountCategory->description ?? 'Other Assets';
        });

        $liabilitiesByCategory = $liabilities->groupBy(function ($account) {
            return $account->accountCategory->description ?? 'Other Liabilities';
        });

        $equityByCategory = $equity->groupBy(function ($account) {
            return $account->accountCategory->description ?? 'Other Equity';
        });

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');

        $isBalanced = round($totalAssets - ($totalLiabilities + $totalEquity), 2) === 0.0;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'assetsByCategory' => $assetsByCategory,
            'liabilitiesByCategory' => $liabilitiesByCategory,
            'equityByCategory' => $equityByCategory,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'isBalanced' => $isBalanced,
        ];
    }

    protected function getAccountBalances(array $accountIds, string $asOf)
    {
        if (empty($accountIds)) {
            return [];
        }

        $organizationId = $this->getOrganizationId();

        $results = gl_journal::where('organization_id', $organizationId)
            ->where('status', 'posted')
            ->where('journal_date', '<=', $asOf)
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