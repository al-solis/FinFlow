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
use App\Traits\AuthorizesAccessRights;
use Carbon\Carbon;
use App\Models\tax_master;
use App\Models\tax_type;
use App\Models\ap_invoice;
use App\Models\rfd_detail_tax;
use App\Models\rfd_detail;
use App\Models\gl_journal_line;


class ReportController extends Controller
{
    use AuthorizesAccessRights;
    use \App\Traits\WithSystemSettings;

    protected function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function index(Request $request)
    {
        $this->authorizeRead(Modules::FIN);

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $reportType = $request->input('report_type', 'trial_balance');

        // Tax report specific filters
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $taxTypeId = $request->input('tax_type_id');

        $reportTypes = [
            'trial_balance' => 'Trial Balance',
            'balance_sheet' => 'Balance Sheet',
            'income_statement' => 'Income Statement',
            'cash_flow' => 'Cash Flow Statement',
            'general_ledger' => 'General Ledger',
            'tax_report' => 'Tax Report',
        ];

        $accountTypes = account_type::where('organization_id', $this->getOrganizationId())
            ->orderBy('code')
            ->get();

        $taxTypes = tax_type::where('organization_id', $this->getOrganizationId())
            ->orderBy('name')
            ->get();
        // Initialize variables for all report types
        $data = [
            'reportTypes' => $reportTypes,
            'accountTypes' => $accountTypes,
            'selectedReport' => $reportType,
            'from' => $from,
            'to' => $to,
            'taxTypes' => $taxTypes,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedTaxType' => $taxTypeId,
        ];

        // Generate the selected report data
        switch ($reportType) {
            case 'trial_balance':
                $data = array_merge($data, $this->generateTrialBalanceData($from, $to));
                break;
            case 'balance_sheet':
                $data = array_merge($data, $this->generateBalanceSheetData($to));
                break;
            case 'income_statement':
                $data = array_merge($data, $this->generateIncomeStatementData($from, $to));
                break;
            case 'cash_flow':
                $data = array_merge($data, $this->generateCashFlowData($from, $to));
                break;
            case 'tax_report':
                $data = array_merge($data, $this->generateTaxReportData($startDate, $endDate, $taxTypeId));
                break;
            default:
                // Default to trial balance
                $data = array_merge($data, $this->generateTrialBalanceData($from, $to));
                break;
        }

        return view('reports.index', $this->withSystemSettings($data));
    }

    /**
     * Generate Trial Balance Data
     */
    protected function generateTrialBalanceData(string $from, string $to): array
    {
        $organizationId = $this->getOrganizationId();

        $rows = gl_journal::where('gl_journals.organization_id', $organizationId)  // Fully qualified
            ->where('gl_journals.status', 'posted')
            ->whereBetween('gl_journals.journal_date', [$from, $to])
            ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'gl_journal_lines.gl_account_id')
            ->join('account_types', 'account_types.id', '=', 'chart_of_accounts.account_type_id')
            ->selectRaw('
            chart_of_accounts.id as account_id,
            chart_of_accounts.account_code,
            chart_of_accounts.account_name,
            account_types.code as account_type_code,
            SUM(gl_journal_lines.debit) as total_debit,
            SUM(gl_journal_lines.credit) as total_credit
        ')
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.account_code', 'chart_of_accounts.account_name', 'account_types.code')
            ->orderBy('chart_of_accounts.account_code')
            ->get()
            ->map(function ($row) {
                $net = (float) $row->total_debit - (float) $row->total_credit;
                $row->debit_balance = $net > 0 ? $net : 0;
                $row->credit_balance = $net < 0 ? -$net : 0;
                return $row;
            });

        $totalDebit = $rows->sum('debit_balance');
        $totalCredit = $rows->sum('credit_balance');
        $isBalanced = round($totalDebit - $totalCredit, 2) === 0.0;

        return [
            'rows' => $rows,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'isBalanced' => $isBalanced,
        ];
    }
    /**
     * Generate Balance Sheet Data
     */
    protected function generateBalanceSheetData(string $asOf): array
    {
        $organizationId = $this->getOrganizationId();

        // Get all accounts with balances as of the specified date
        $accountBalances = $this->getAccountBalances($asOf);

        // Group by account type for balance sheet presentation
        $assets = $accountBalances->filter(function ($account) {
            return in_array($account->account_type_code, ['ASSET']);
        })->values();

        $liabilities = $accountBalances->filter(function ($account) {
            return in_array($account->account_type_code, ['LIABILITY']);
        })->values();

        $equity = $accountBalances->filter(function ($account) {
            return in_array($account->account_type_code, ['EQUITY']);
        })->values();

        // Calculate totals
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');

        $isBalanced = round($totalAssets - ($totalLiabilities + $totalEquity), 2) === 0.0;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'asOf' => $asOf,
            'isBalanced' => $isBalanced,
        ];
    }

    /**
     * Generate Income Statement Data
     */
    protected function generateIncomeStatementData(string $from, string $to): array
    {
        $organizationId = $this->getOrganizationId();

        $accountBalances = $this->getAccountBalancesByDateRange($from, $to);

        // Income (Revenue) accounts
        $revenue = $accountBalances->filter(function ($account) {
            return in_array($account->account_type_code, ['REVENUE', 'INCOME']);
        })->values();

        // Expense accounts
        $expenses = $accountBalances->filter(function ($account) {
            return in_array($account->account_type_code, ['EXPENSE', 'COST']);
        })->values();

        // Cost of Goods Sold
        $cogs = $accountBalances->filter(function ($account) {
            return $account->account_type_code === 'COST' &&
                (str_contains(strtoupper($account->account_name), 'GOODS') ||
                    str_contains(strtoupper($account->account_name), 'SOLD') ||
                    str_contains(strtoupper($account->account_name), 'COGS'));
        })->values();

        // Other Income
        $otherIncome = $accountBalances->filter(function ($account) {
            return $account->account_type_code === 'REVENUE' &&
                (str_contains(strtoupper($account->account_name), 'OTHER') ||
                    str_contains(strtoupper($account->account_name), 'NON-OPERATING'));
        })->values();

        // Other Expenses
        $otherExpenses = $accountBalances->filter(function ($account) {
            return $account->account_type_code === 'EXPENSE' &&
                (str_contains(strtoupper($account->account_name), 'OTHER') ||
                    str_contains(strtoupper($account->account_name), 'NON-OPERATING'));
        })->values();

        // Calculate totals
        $totalRevenue = $revenue->sum('balance');
        $totalExpenses = $expenses->sum('balance');
        $totalCogs = $cogs->sum('balance');
        $totalOtherIncome = $otherIncome->sum('balance');
        $totalOtherExpenses = $otherExpenses->sum('balance');

        $grossProfit = $totalRevenue - $totalCogs;
        $operatingIncome = $grossProfit - ($totalExpenses - $totalCogs);
        $netIncome = $operatingIncome + $totalOtherIncome - $totalOtherExpenses;

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'cogs' => $cogs,
            'otherIncome' => $otherIncome,
            'otherExpenses' => $otherExpenses,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'totalCogs' => $totalCogs,
            'totalOtherIncome' => $totalOtherIncome,
            'totalOtherExpenses' => $totalOtherExpenses,
            'grossProfit' => $grossProfit,
            'operatingIncome' => $operatingIncome,
            'netIncome' => $netIncome,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Generate Cash Flow Data
     */
    // protected function generateCashFlowData(string $from, string $to): array
    // {
    //     $organizationId = $this->getOrganizationId();

    //     $currencySymbol = SystemSettings::getCurrencySymbol();

    //     // 1. Fetch Cash & Bank Accounts
    //     $cashAccounts = chart_of_account::where('chart_of_accounts.organization_id', $organizationId)
    //         ->where('chart_of_accounts.is_posting', true)
    //         ->whereHas('accountType', function ($q) {
    //             $q->where('code', 'ASSET');
    //         })
    //         ->where(function ($q) {
    //             $q->where('chart_of_accounts.account_name', 'LIKE', '%Cash%')
    //                 ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Bank%')
    //                 ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Checking%')
    //                 ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Savings%')
    //                 ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Money Market%');
    //         })
    //         ->pluck('chart_of_accounts.id')
    //         ->toArray();

    //     if (empty($cashAccounts)) {
    //         $cashAccounts = chart_of_account::where('chart_of_accounts.organization_id', $organizationId)
    //             ->where('chart_of_accounts.is_posting', true)
    //             ->whereHas('accountType', function ($q) {
    //                 $q->where('code', 'ASSET');
    //             })
    //             ->pluck('chart_of_accounts.id')
    //             ->toArray();
    //     }

    //     // 2. Query Cash Movements
    //     $cashMovements = gl_journal::where('gl_journals.organization_id', $organizationId)
    //         ->where('gl_journals.status', 'posted')
    //         ->whereBetween('gl_journals.journal_date', [$from, $to])
    //         ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
    //         ->whereIn('gl_journal_lines.gl_account_id', $cashAccounts)
    //         ->selectRaw('
    //         gl_journals.source_module,
    //         SUM(gl_journal_lines.debit) as total_debit,
    //         SUM(gl_journal_lines.credit) as total_credit
    //     ')
    //         ->groupBy('gl_journals.source_module')
    //         ->get()
    //         ->map(function ($m) {
    //             $m->net_change = (float) $m->total_debit - (float) $m->total_credit;
    //             return $m;
    //         });

    //     // 3. Calculate Opening & Closing Balances
    //     $openingCash = $this->getOpeningCashBalance($from, $cashAccounts);
    //     $totalNetChange = $cashMovements->sum('net_change');
    //     $closingCash = $openingCash + $totalNetChange;

    //     // 4. Categorize Activities
    //     $operatingModules = ['rfd', 'ca_disbursement', 'liquidation', 'reimbursement_disbursement', 'journal', 'sales', 'ar'];
    //     $investingModules = ['fixed_assets', 'investment'];
    //     $financingModules = ['loan', 'equity', 'capital'];

    //     $operatingActivities = $cashMovements->filter(fn($m) => in_array($m->source_module, $operatingModules));
    //     $investingActivities = $cashMovements->filter(fn($m) => in_array($m->source_module, $investingModules));
    //     $financingActivities = $cashMovements->filter(fn($m) => in_array($m->source_module, $financingModules));

    //     // Catch-all for unclassified modules -> route to Operating
    //     $otherMovements = $cashMovements->reject(
    //         fn($m) =>
    //         in_array($m->source_module, array_merge($operatingModules, $investingModules, $financingModules))
    //     );
    //     $operatingActivities = $operatingActivities->concat($otherMovements);

    //     // 5. Compute Net Totals per Activity
    //     $netOperating = $operatingActivities->sum('net_change');
    //     $netInvesting = $investingActivities->sum('net_change');
    //     $netFinancing = $financingActivities->sum('net_change');

    //     return [
    //         'cashMovements' => $cashMovements,
    //         'operatingActivities' => $operatingActivities,
    //         'investingActivities' => $investingActivities,
    //         'financingActivities' => $financingActivities,
    //         'netOperating' => $netOperating,
    //         'netInvesting' => $netInvesting,
    //         'netFinancing' => $netFinancing,
    //         'openingCash' => $openingCash,
    //         'closingCash' => $closingCash,
    //         'currencySymbol' => $currencySymbol,
    //         'from' => $from,
    //         'to' => $to,
    //     ];
    // }


    protected function generateCashFlowData(string $from, string $to): array
    {
        $organizationId = $this->getOrganizationId();
        $currencySymbol = SystemSettings::getCurrencySymbol();

        // 1. Fetch Cash & Bank Accounts
        $cashAccounts = chart_of_account::where('chart_of_accounts.organization_id', $organizationId)
            ->where('chart_of_accounts.is_posting', true)
            ->whereHas('accountType', function ($q) {
                $q->where('code', 'ASSET');
            })
            ->where(function ($q) {
                $q->where('chart_of_accounts.account_name', 'LIKE', '%Cash%')
                    ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Bank%')
                    ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Checking%')
                    ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Savings%')
                    ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Money Market%');
            })
            ->pluck('chart_of_accounts.id')
            ->toArray();

        // Fallback: if no specific cash accounts found, use all asset accounts
        if (empty($cashAccounts)) {
            $cashAccounts = chart_of_account::where('chart_of_accounts.organization_id', $organizationId)
                ->where('chart_of_accounts.is_posting', true)
                ->whereHas('accountType', function ($q) {
                    $q->where('code', 'ASSET');
                })
                ->pluck('chart_of_accounts.id')
                ->toArray();
        }

        // 2. Get Daily Cash Movements
        $dailyMovements = gl_journal::where('gl_journals.organization_id', $organizationId)
            ->where('gl_journals.status', 'posted')
            ->whereBetween('gl_journals.journal_date', [$from, $to])
            ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->whereIn('gl_journal_lines.gl_account_id', $cashAccounts)
            ->selectRaw('
                gl_journals.journal_date,
                gl_journals.source_module,
                SUM(gl_journal_lines.debit) as total_debit,
                SUM(gl_journal_lines.credit) as total_credit
            ')
            ->groupBy('gl_journals.journal_date', 'gl_journals.source_module')
            ->orderBy('gl_journals.journal_date')
            ->get()
            ->map(function ($m) {
                $m->net_change = (float) $m->total_debit - (float) $m->total_credit;
                return $m;
            });

        // 3. Aggregate by Source Module
        $cashMovements = gl_journal::where('gl_journals.organization_id', $organizationId)
            ->where('gl_journals.status', 'posted')
            ->whereBetween('gl_journals.journal_date', [$from, $to])
            ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->whereIn('gl_journal_lines.gl_account_id', $cashAccounts)
            ->selectRaw('
                gl_journals.source_module,
                SUM(gl_journal_lines.debit) as total_debit,
                SUM(gl_journal_lines.credit) as total_credit
            ')
            ->groupBy('gl_journals.source_module')
            ->get()
            ->map(function ($m) {
                $m->net_change = (float) $m->total_debit - (float) $m->total_credit;
                return $m;
            });

        // 4. Calculate Opening & Closing Balances
        $openingCash = $this->getOpeningCashBalance($from, $cashAccounts);
        $totalNetChange = $cashMovements->sum('net_change');
        $closingCash = $openingCash + $totalNetChange;

        // 5. Categorize Activities
        $operatingModules = [
            'rfd',
            'ca_disbursement',
            'liquidation',
            'reimbursement_disbursement',
            'journal',
            'sales',
            'ar',
            'payment',
            'ap'
        ];
        $investingModules = ['fixed_assets', 'investment', 'asset_purchase', 'asset_sale'];
        $financingModules = ['loan', 'equity', 'capital', 'dividend', 'owner_contribution'];

        $operatingActivities = $cashMovements->filter(fn($m) => in_array($m->source_module, $operatingModules));
        $investingActivities = $cashMovements->filter(fn($m) => in_array($m->source_module, $investingModules));
        $financingActivities = $cashMovements->filter(fn($m) => in_array($m->source_module, $financingModules));

        // Unclassified modules go to Operating
        $allKnownModules = array_merge($operatingModules, $investingModules, $financingModules);
        $otherMovements = $cashMovements->reject(fn($m) => in_array($m->source_module, $allKnownModules));
        $operatingActivities = $operatingActivities->concat($otherMovements);

        // 6. Compute Net Totals per Activity
        $netOperating = $operatingActivities->sum('net_change');
        $netInvesting = $investingActivities->sum('net_change');
        $netFinancing = $financingActivities->sum('net_change');

        // 7. Build Daily Cash Flow for Chart
        $dailyData = $dailyMovements->groupBy('journal_date')->map(function ($items, $date) {
            return [
                'date' => $date,
                'net_change' => $items->sum('net_change'),
                'details' => $items->map(function ($item) {
                    return [
                        'source' => $item->source_module,
                        'net_change' => $item->net_change,
                    ];
                }),
            ];
        })->values();

        return [
            'cashMovements' => $cashMovements,
            'dailyMovements' => $dailyData,
            'operatingActivities' => $operatingActivities,
            'investingActivities' => $investingActivities,
            'financingActivities' => $financingActivities,
            'netOperating' => $netOperating,
            'netInvesting' => $netInvesting,
            'netFinancing' => $netFinancing,
            'openingCash' => $openingCash,
            'closingCash' => $closingCash,
            'totalNetChange' => $totalNetChange,
            'currencySymbol' => $currencySymbol,
            'from' => $from,
            'to' => $to,
        ];
    }


    /**
     * Generate Tax Report Data
     */
    protected function generateTaxReportData(string $startDate, string $endDate, ?int $taxTypeId = null): array
    {
        $organizationId = $this->getOrganizationId();

        // Get tax masters
        $taxMastersQuery = tax_master::with(['taxType', 'taxFormula', 'glAccount'])
            ->where('organization_id', $organizationId)
            ->where('status', 1);

        if ($taxTypeId) {
            $taxMastersQuery->where('tax_type_id', $taxTypeId);
        }

        $taxMasters = $taxMastersQuery->orderBy('code')->get();

        // Get RFD Detail Taxes (Source of truth for tax on invoices)
        $rfdTaxes = rfd_detail_tax::with([
            'rfdDetail.rfdHeader',
            'rfdDetail.vendor',
            'tax'
        ])
            ->whereHas('rfdDetail.rfdHeader', function ($q) use ($organizationId, $startDate, $endDate) {
                $q->where('organization_id', $organizationId)
                    ->where('approval_status', '2') // Approved RFDs only
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->get();

        // Get AP Invoices for reference
        $apInvoices = ap_invoice::with(['vendor:id,name'])
            ->where('organization_id', $organizationId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get();

        // Build Tax Summary from RFD Tax Details
        $summary = $taxMasters->map(function ($tax) use ($rfdTaxes) {
            // Filter tax details for this tax master
            $taxDetails = $rfdTaxes->filter(function ($detail) use ($tax) {
                return $detail->tax_id == $tax->id;
            });

            $totalTaxable = $taxDetails->sum('taxable_amount');
            $totalTaxAmount = $taxDetails->sum('tax_amount');

            // Calculate GL balance from posted journals
            $glBalance = $this->getTaxGlBalance($tax->gl_account_id);

            return [
                'code' => $tax->code,
                'name' => $tax->name,
                'type' => $tax->taxType->name ?? 'N/A',
                'rate' => (float) $tax->rate ?? 0,
                'operation' => $tax->taxFormula->operation ?? 'Add',
                'gl_account' => $tax->glAccount->account_code ?? 'Not Assigned',
                'gl_account_name' => $tax->glAccount->account_name ?? 'Not Assigned',
                'taxable_amount' => $totalTaxable,
                'tax_amount' => $totalTaxAmount,
                'gl_balance' => $glBalance,
                'transaction_count' => $taxDetails->count(),
            ];
        });

        // Build Detailed Tax Transactions
        $taxTransactions = $rfdTaxes->map(function ($detail) {
            $rfd = $detail->rfdDetail->rfdHeader ?? null;
            return [
                'rfd_id' => $rfd->id ?? null,
                'rfd_reference' => $rfd ? 'RFD-' . str_pad($rfd->id, 6, '0', STR_PAD_LEFT) : 'N/A',
                'date' => $rfd->created_at ?? null,
                'vendor_name' => $detail->rfdDetail->vendor->name ?? 'N/A',
                'tax_code' => $detail->tax->code ?? 'N/A',
                'tax_name' => $detail->tax->name ?? 'N/A',
                'taxable_amount' => $detail->taxable_amount,
                'tax_amount' => $detail->tax_amount,
                'description' => $detail->rfdDetail->description ?? '',
            ];
        });

        // Calculate totals
        $totals = [
            'total_taxable' => $summary->sum('taxable_amount'),
            'total_tax_amount' => $summary->sum('tax_amount'),
            'total_gl_balance' => $summary->sum('gl_balance'),
            'total_transactions' => $rfdTaxes->count(),
        ];

        return [
            'summary' => $summary,
            'taxTransactions' => $taxTransactions,
            'apInvoices' => $apInvoices,
            'totals' => $totals,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    protected function getTaxGlBalance($glAccountId)
    {
        if (!$glAccountId) {
            return 0;
        }

        $organizationId = $this->getOrganizationId();

        $result = gl_journal_line::where('gl_account_id', $glAccountId)
            ->whereHas('journal', function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                    ->where('status', 'posted');
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return ((float) ($result->total_debit ?? 0)) - ((float) ($result->total_credit ?? 0));
    }

    /**
     * Get account balances as of a specific date
     */
    protected function getAccountBalances(string $asOf)
    {
        $organizationId = $this->getOrganizationId();

        return gl_journal::where('gl_journals.organization_id', $organizationId)  // Fully qualified
            ->where('gl_journals.status', 'posted')
            ->where('gl_journals.journal_date', '<=', $asOf)
            ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'gl_journal_lines.gl_account_id')
            ->join('account_types', 'account_types.id', '=', 'chart_of_accounts.account_type_id')
            ->selectRaw('
            chart_of_accounts.id as account_id,
            chart_of_accounts.account_code,
            chart_of_accounts.account_name,
            account_types.code as account_type_code,
            SUM(gl_journal_lines.debit) as total_debit,
            SUM(gl_journal_lines.credit) as total_credit
        ')
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.account_code', 'chart_of_accounts.account_name', 'account_types.code')
            ->orderBy('chart_of_accounts.account_code')
            ->get()
            ->map(function ($row) {
                $net = (float) $row->total_debit - (float) $row->total_credit;
                $row->balance = $net;
                return $row;
            });
    }
    /**
     * Get account balances for a date range
     */
    protected function getAccountBalancesByDateRange(string $from, string $to)
    {
        $organizationId = $this->getOrganizationId();

        return gl_journal::where('gl_journals.organization_id', $organizationId)  // Fully qualified
            ->where('gl_journals.status', 'posted')
            ->whereBetween('gl_journals.journal_date', [$from, $to])
            ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'gl_journal_lines.gl_account_id')
            ->join('account_types', 'account_types.id', '=', 'chart_of_accounts.account_type_id')
            ->selectRaw('
            chart_of_accounts.id as account_id,
            chart_of_accounts.account_code,
            chart_of_accounts.account_name,
            account_types.code as account_type_code,
            SUM(gl_journal_lines.debit) as total_debit,
            SUM(gl_journal_lines.credit) as total_credit
        ')
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.account_code', 'chart_of_accounts.account_name', 'account_types.code')
            ->orderBy('chart_of_accounts.account_code')
            ->get()
            ->map(function ($row) {
                $net = (float) $row->total_debit - (float) $row->total_credit;
                $row->balance = $net;
                return $row;
            });
    }

    /**
     * Get opening cash balance as of a date
     */
    protected function getOpeningCashBalance(string $asOf, array $cashAccountIds)
    {
        $organizationId = $this->getOrganizationId();

        $result = gl_journal::where('gl_journals.organization_id', $organizationId)
            ->where('gl_journals.status', 'posted')
            ->where('gl_journals.journal_date', '<', $asOf)
            ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->whereIn('gl_journal_lines.gl_account_id', $cashAccountIds)
            ->selectRaw('SUM(gl_journal_lines.debit) as total_debit, SUM(gl_journal_lines.credit) as total_credit')
            ->first();

        return ((float) ($result->total_debit ?? 0)) - ((float) ($result->total_credit ?? 0));
    }

    // ==================== DEDICATED REPORT ENDPOINTS ====================

    public function trialBalance(Request $request)
    {
        $this->authorizeRead(Modules::FIN, Modules::FIN_TB);
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $data = $this->generateTrialBalanceData($from, $to);
        return view('reports.partials.trial_balance', array_merge($data, ['from' => $from, 'to' => $to]));
    }

    public function balanceSheet(Request $request)
    {
        $this->authorizeRead(Modules::FIN, Modules::FIN_BS);
        $asOf = $request->input('as_of', now()->toDateString());
        $data = $this->generateBalanceSheetData($asOf);
        return view('fin.balance_sheet', $data);
    }

    public function incomeStatement(Request $request)
    {
        $this->authorizeRead(Modules::FIN, Modules::FIN_IS);
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $data = $this->generateIncomeStatementData($from, $to);
        return view('fin.income_statement', $data);
    }

    public function cashFlow(Request $request)
    {
        $this->authorizeRead(Modules::FIN, Modules::FIN_CF);
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $data = $this->generateCashFlowData($from, $to);
        return view('fin.cash_flow', $data);
    }
}