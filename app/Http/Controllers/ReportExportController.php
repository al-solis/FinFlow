<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Services\SystemSettings;
use App\Models\gl_journal;
use App\Models\chart_of_account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ap_invoice;
use App\Models\tax_type;
use App\Models\tax_master;

class ReportExportController extends Controller
{
    use \App\Traits\AuthorizesAccessRights;

    protected function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function export(Request $request)
    {
        $this->authorizeRead(Modules::FIN);

        $type = $request->input('type', 'trial_balance');
        $format = $request->input('format', 'csv');

        // Get organization ID
        $orgId = $this->getOrganizationId();
        if (!$orgId) {
            return back()->with('error', 'Organization settings not found. Please configure your organization first.');
        }

        switch ($type) {
            case 'balance_sheet':
                return $this->exportBalanceSheet($request, $format, $orgId);
            case 'income_statement':
                return $this->exportIncomeStatement($request, $format, $orgId);
            case 'cash_flow':
                return $this->exportCashFlow($request, $format, $orgId);
            case 'trial_balance':
            default:
                return $this->exportTrialBalance($request, $format, $orgId);
        }
    }

    protected function exportTrialBalance(Request $request, string $format, int $orgId)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        \Log::info('Exporting Trial Balance', ['from' => $from, 'to' => $to, 'org_id' => $orgId]);

        $rows = gl_journal::where('gl_journals.organization_id', $orgId)
            ->where('gl_journals.status', 'posted')
            ->whereBetween('gl_journals.journal_date', [$from, $to])
            ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'gl_journal_lines.gl_account_id')
            ->join('account_types', 'account_types.id', '=', 'chart_of_accounts.account_type_id')
            ->selectRaw('
                chart_of_accounts.account_code,
                chart_of_accounts.account_name,
                account_types.code as account_type,
                SUM(gl_journal_lines.debit) as total_debit,
                SUM(gl_journal_lines.credit) as total_credit
            ')
            ->groupBy('chart_of_accounts.account_code', 'chart_of_accounts.account_name', 'account_types.code')
            ->orderBy('chart_of_accounts.account_code')
            ->get();

        \Log::info('Trial Balance rows found', ['count' => $rows->count()]);

        if ($rows->isEmpty()) {
            return back()->with('warning', 'No data found for the selected period. Please adjust your date range.');
        }

        $filename = 'trial_balance_' . date('Y-m-d') . '.csv';

        return $this->exportCSV($rows, $filename, [
            'Account Code',
            'Account Name',
            'Account Type',
            'Debit',
            'Credit'
        ], function ($row) {
            return [
                $row->account_code,
                $row->account_name,
                $row->account_type,
                number_format((float) $row->total_debit, 2),
                number_format((float) $row->total_credit, 2),
            ];
        });
    }

    protected function exportBalanceSheet(Request $request, string $format, int $orgId)
    {
        $asOf = $request->input('as_of', now()->toDateString());

        \Log::info('Exporting Balance Sheet', ['as_of' => $asOf, 'org_id' => $orgId]);

        $accountBalances = $this->getAccountBalances($asOf, $orgId);

        if ($accountBalances->isEmpty()) {
            return back()->with('warning', 'No data found for the selected date. Please adjust your date.');
        }

        $filename = 'balance_sheet_' . date('Y-m-d') . '.csv';

        return $this->exportCSV(
            $accountBalances,
            $filename,
            ['Account Code', 'Account Name', 'Account Type', 'Balance'],
            function ($row) {
                return [
                    $row->account_code,
                    $row->account_name,
                    $row->account_type_code,
                    number_format((float) $row->balance, 2),
                ];
            }
        );
    }

    protected function exportIncomeStatement(Request $request, string $format, int $orgId)
    {
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to = $request->input('to', now()->toDateString());

        \Log::info('Exporting Income Statement', ['from' => $from, 'to' => $to, 'org_id' => $orgId]);

        $accountBalances = $this->getAccountBalancesByDateRange($from, $to, $orgId);

        if ($accountBalances->isEmpty()) {
            return back()->with('warning', 'No data found for the selected period. Please adjust your date range.');
        }

        $filename = 'income_statement_' . date('Y-m-d') . '.csv';

        return $this->exportCSV(
            $accountBalances,
            $filename,
            ['Account Code', 'Account Name', 'Account Type', 'Balance'],
            function ($row) {
                return [
                    $row->account_code,
                    $row->account_name,
                    $row->account_type_code,
                    number_format((float) $row->balance, 2),
                ];
            }
        );
    }

    protected function exportCashFlow(Request $request, string $format, int $orgId)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        \Log::info('Exporting Cash Flow', ['from' => $from, 'to' => $to, 'org_id' => $orgId]);

        // Get cash accounts
        $cashAccounts = chart_of_account::where('chart_of_accounts.organization_id', $orgId)
            ->where('chart_of_accounts.is_posting', true)
            ->whereHas('accountType', function ($q) {
                $q->where('code', 'ASSET');
            })
            ->where(function ($q) {
                $q->where('chart_of_accounts.account_name', 'LIKE', '%Cash%')
                    ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Bank%')
                    ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Checking%')
                    ->orWhere('chart_of_accounts.account_name', 'LIKE', '%Savings%');
            })
            ->pluck('chart_of_accounts.id')
            ->toArray();

        if (empty($cashAccounts)) {
            $cashAccounts = chart_of_account::where('chart_of_accounts.organization_id', $orgId)
                ->where('chart_of_accounts.is_posting', true)
                ->whereHas('accountType', function ($q) {
                    $q->where('code', 'ASSET');
                })
                ->pluck('chart_of_accounts.id')
                ->toArray();
        }

        \Log::info('Cash accounts found', ['count' => count($cashAccounts)]);

        if (empty($cashAccounts)) {
            return back()->with('warning', 'No cash accounts found. Please configure your chart of accounts.');
        }

        $movements = gl_journal::where('gl_journals.organization_id', $orgId)
            ->where('gl_journals.status', 'posted')
            ->whereBetween('gl_journals.journal_date', [$from, $to])
            ->join('gl_journal_lines', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
            ->whereIn('gl_journal_lines.gl_account_id', $cashAccounts)
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'gl_journal_lines.gl_account_id')
            ->selectRaw('
                chart_of_accounts.account_code,
                chart_of_accounts.account_name,
                gl_journals.source_module,
                gl_journals.journal_date,
                gl_journal_lines.debit,
                gl_journal_lines.credit
            ')
            ->orderBy('gl_journals.journal_date')
            ->get();

        if ($movements->isEmpty()) {
            return back()->with('warning', 'No cash movements found for the selected period.');
        }

        $filename = 'cash_flow_' . date('Y-m-d') . '.csv';

        return $this->exportCSV(
            $movements,
            $filename,
            ['Date', 'Account Code', 'Account Name', 'Source Module', 'Debit', 'Credit'],
            function ($row) {
                return [
                    Carbon::parse($row->journal_date)->format('Y-m-d'),
                    $row->account_code,
                    $row->account_name,
                    $row->source_module,
                    number_format((float) $row->debit, 2),
                    number_format((float) $row->credit, 2),
                ];
            }
        );
    }

    protected function exportCSV($data, string $filename, array $headers, callable $rowMapper)
    {
        $handle = fopen('php://temp', 'r+');

        // Add BOM for UTF-8
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write headers
        fputcsv($handle, $headers);

        // Write data
        foreach ($data as $row) {
            fputcsv($handle, $rowMapper($row));
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    protected function getAccountBalances(string $asOf, int $orgId)
    {
        return gl_journal::where('gl_journals.organization_id', $orgId)
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

    protected function getAccountBalancesByDateRange(string $from, string $to, int $orgId)
    {
        return gl_journal::where('gl_journals.organization_id', $orgId)
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
}