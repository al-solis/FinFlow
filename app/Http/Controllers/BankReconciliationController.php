<?php

namespace App\Http\Controllers;

use App\Models\bank_account;
use App\Models\bank_reconciliation_import;
use App\Models\bank_reconciliation_line;
use App\Models\chart_of_account;
use App\Services\AccountingService;
use App\Services\SystemSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\account_structure;

class BankReconciliationController extends Controller
{
    public function __construct(protected AccountingService $accounting)
    {
    }

    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    public function index()
    {
        $organizationId = $this->getOrganizationId();

        $bankAccounts = bank_account::where('organization_id', $organizationId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $imports = bank_reconciliation_import::with(['bankAccount', 'creator'])
            ->where('organization_id', $organizationId)
            ->orderByDesc('id')
            ->paginate(config('app.paginate', 15));

        return view('bm.recon.index', compact('bankAccounts', 'imports'));
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'statement_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $bankAccount = bank_account::findOrFail($data['bank_account_id']);

        $handle = fopen($request->file('statement_file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($handle);

        if (!$rawHeader) {
            fclose($handle);
            return back()->withErrors(['statement_file' => 'The file appears to be empty.']);
        }

        // Strip leading "*" (required-field marker) and normalize for matching
        $map = [];
        foreach ($rawHeader as $i => $name) {
            $key = strtolower(trim(ltrim(trim($name), '*')));
            $map[$key] = $i;
        }

        foreach (['date', 'amount'] as $required) {
            if (!isset($map[$required])) {
                fclose($handle);
                return back()->withErrors(['statement_file' => "CSV is missing the required \"{$required}\" column."]);
            }
        }

        $import = bank_reconciliation_import::create([
            'organization_id' => $bankAccount->organization_id,
            'bank_account_id' => $bankAccount->id,
            'original_filename' => $request->file('statement_file')->getClientOriginalName(),
            'status' => 'pending_review',
            'created_by' => Auth::id(),
        ]);

        $lineNo = 0;
        $dates = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) {
                continue; // skip blank rows
            }

            $lineNo++;

            $rawDate = trim($row[$map['date']] ?? '');
            $rawAmount = trim($row[$map['amount']] ?? '0');
            $date = $this->parseDate($rawDate);
            $amount = (float) str_replace([',', '$'], '', $rawAmount);

            if ($date) {
                $dates[] = $date;
            }

            bank_reconciliation_line::create([
                'bank_reconciliation_import_id' => $import->id,
                'line_no' => $lineNo,
                'transaction_date' => $date ?? now()->toDateString(),
                'amount' => $amount,
                'payee' => isset($map['payee']) ? trim($row[$map['payee']] ?? '') ?: null : null,
                'description' => isset($map['description']) ? trim($row[$map['description']] ?? '') ?: null : null,
                'reference' => isset($map['reference']) ? trim($row[$map['reference']] ?? '') ?: null : null,
                'check_number' => isset($map['check number']) ? trim($row[$map['check number']] ?? '') ?: null : null,
                'accepted' => true,
                'status' => 'pending',
            ]);
        }

        fclose($handle);

        $import->update([
            'total_lines' => $lineNo,
            'statement_from' => $dates ? min($dates) : null,
            'statement_to' => $dates ? max($dates) : null,
        ]);

        return redirect()->route('bm.recon.review', $import->id)
            ->with('success', "{$lineNo} line item(s) imported. Review and post below.");
    }

    protected function parseDate(string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function review(bank_reconciliation_import $import)
    {
        $import->load(['bankAccount', 'lines' => fn($q) => $q->orderBy('line_no')]);

        $structure = account_structure::where('organization_id', $import->organization_id)
            ->where('status', true)
            ->where('is_default', true)
            ->value('id');

        $glAccounts = chart_of_account::where('organization_id', $import->organization_id)
            ->where('is_posting', true)
            ->where('account_structure_id', $structure)
            ->where('status', true)
            ->orderBy('account_code')
            ->get();

        return view('bm.recon.review', compact('import', 'glAccounts'));
    }

    public function post(Request $request, bank_reconciliation_import $import)
    {
        $data = $request->validate([
            'accepted' => 'array',
            'accepted.*' => 'boolean',
            'gl_account_id' => 'array',
            'gl_account_id.*' => 'nullable|exists:chart_of_accounts,id',
        ]);

        $lines = $import->lines()->where('status', 'pending')->get();
        $posted = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $isAccepted = (bool) ($data['accepted'][$line->id] ?? false);
            $glAccountId = $data['gl_account_id'][$line->id] ?? null;

            if (!$isAccepted) {
                $line->update(['status' => 'skipped']);
                $skipped++;
                continue;
            }

            if (!$glAccountId) {
                return back()->withErrors([
                    "line_{$line->id}" => "Line #{$line->line_no}: select an offsetting GL account before posting, or uncheck it to skip.",
                ]);
            }

            $journal = $this->accounting->postBankStatementLine($import->bankAccount, $line, (int) $glAccountId, Auth::id());

            $line->update([
                'gl_account_id' => $glAccountId,
                'status' => 'posted',
                'gl_journal_id' => $journal->id,
            ]);
            $posted++;
        }

        if ($import->lines()->where('status', 'pending')->doesntExist()) {
            $import->update(['status' => 'posted', 'posted_at' => now(), 'posted_by' => Auth::id()]);
        }

        return redirect()->route('bm.recon.review', $import->id)
            ->with('success', "{$posted} line(s) posted" . ($skipped ? ", {$skipped} skipped." : '.'));
    }
}
