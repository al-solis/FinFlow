<?php

namespace App\Http\Controllers;

use App\Models\chart_of_account;
use App\Models\gl_journal_line;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use App\Models\account_structure;

class GeneralLedgerController extends Controller
{
    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function index(Request $request)
    {
        $organizationId = $this->getOrganizationId();
        $structureId = account_structure::where('organization_id', $organizationId)
            ->where('status', true)
            ->where('is_default', true)
            ->value('id');

        $accounts = chart_of_account::where('organization_id', $organizationId)       
            ->where('account_structure_id', $structureId)    
            ->where('is_posting', true)
            ->where('status', true)
            ->orderBy('account_code')
            ->get();

        $accountId = $request->input('account_id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $account = null;
        $entries = collect();
        $openingBalance = 0;
        $isDebitNormal = true;

        if ($accountId) {
            $account = chart_of_account::with('accountType')->findOrFail($accountId);
            $isDebitNormal = in_array($account->accountType->code ?? '', ['ASSET', 'EXPENSE'], true);

            $opening = gl_journal_line::query()
                ->join('gl_journals', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
                ->where('gl_journal_lines.gl_account_id', $accountId)
                ->where('gl_journals.status', 'posted')
                ->where('gl_journals.journal_date', '<', $from)
                ->selectRaw('SUM(gl_journal_lines.debit) as d, SUM(gl_journal_lines.credit) as c')
                ->first();

            $openingDebit = (float) ($opening->d ?? 0);
            $openingCredit = (float) ($opening->c ?? 0);
            $openingBalance = $isDebitNormal
                ? ($openingDebit - $openingCredit)
                : ($openingCredit - $openingDebit);

            $lines = gl_journal_line::query()
                ->join('gl_journals', 'gl_journals.id', '=', 'gl_journal_lines.gl_journal_id')
                ->where('gl_journal_lines.gl_account_id', $accountId)
                ->where('gl_journals.status', 'posted')
                ->whereBetween('gl_journals.journal_date', [$from, $to])
                ->orderBy('gl_journals.journal_date')
                ->orderBy('gl_journal_lines.id')
                ->select(
                    'gl_journal_lines.*',
                    'gl_journals.journal_no',
                    'gl_journals.journal_date',
                    'gl_journals.source_module',
                    'gl_journals.description as journal_description'
                )
                ->get();

            $running = $openingBalance;
            $entries = $lines->map(function ($line) use (&$running, $isDebitNormal) {
                $running += $isDebitNormal
                    ? ((float) $line->debit - (float) $line->credit)
                    : ((float) $line->credit - (float) $line->debit);
                $line->running_balance = $running;
                return $line;
            });
        }

        $closingBalance = $entries->isNotEmpty() ? $entries->last()->running_balance : $openingBalance;

        return view('gl.general-ledger', compact(
            'accounts', 'accountId', 'account', 'from', 'to',
            'entries', 'openingBalance', 'closingBalance', 'isDebitNormal'
        ));
    }
}