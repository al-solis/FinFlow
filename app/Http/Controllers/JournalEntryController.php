<?php

namespace App\Http\Controllers;

use App\Models\gl_journal;
use App\Models\gl_journal_line;
use App\Models\chart_of_account;
use App\Models\approval_transaction;
use App\Services\ApprovalWorkflowService;
use App\Services\SystemSettings;
use App\Constants\Modules;
use App\Traits\WithSystemSettings;
use App\Traits\AuthorizesAccessRights;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\approval_workflow;

class JournalEntryController extends Controller
{
    use AuthorizesAccessRights;
    use WithSystemSettings;

    protected ApprovalWorkflowService $approvals;

    public function __construct(ApprovalWorkflowService $approvals)
    {
        $this->approvals = $approvals;
    }

    private function getOrganizationId()
    {
        $settings = SystemSettings::get();
        return $settings ? $settings->id : null;
    }

    /**
     * Generate unique journal number
     */
    private function generateJournalNumber(): string
    {
        $date = now()->format('Ymd');
        $count = gl_journal::whereDate('created_at', now()->toDateString())->count() + 1;
        return 'GJ-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Display a listing of journal entries
     */
    public function index(Request $request)
    {
        $this->authorizeRead(Modules::GL, Modules::GL_JOURNAL);

        $user = Auth::user();

        $journals = gl_journal::query()
            ->with(['creator', 'lines.glAccount', 'latestApprovalTransaction'])
            ->when($user->role_id != 1, function ($q) use ($user) {
                return $q->where('created_by', $user->id);
            })
            ->when($request->filled('searchstatus'), function ($q) use ($request) {
                return $q->where('approval_status', $request->searchstatus);
            })
            ->when($request->filled('searchtype'), function ($q) use ($request) {
                return $q->where('journal_type', $request->searchtype);
            })
            ->when($request->filled('datefrom'), function ($q) use ($request) {
                return $q->whereDate('journal_date', '>=', $request->datefrom);
            })
            ->when($request->filled('dateto'), function ($q) use ($request) {
                return $q->whereDate('journal_date', '<=', $request->dateto);
            })
            ->orderByDesc('id')
            ->paginate(config('app.paginate', 15));

        $stats = [
            'total' => gl_journal::count(),
            'pending' => gl_journal::where('approval_status', '1')->count(),
            'posted' => gl_journal::where('approval_status', '2')->count(),
            'draft' => gl_journal::where('approval_status', '0')->count(),
            'rejected' => gl_journal::where('approval_status', '3')->count(),
        ];

        return view('gl.journal.index', compact('journals', 'stats'));
    }

    /**
     * Show the form for creating a new journal entry
     */
    public function create()
    {
        $this->authorizeCreate(Modules::GL, Modules::GL_JOURNAL);

        $glAccounts = chart_of_account::with('structure', 'accountType')
            ->where('organization_id', $this->getOrganizationId())
            ->whereHas('structure', fn($q) => $q->where('status', 1)->where('is_default', true))
            ->where('is_posting', true)
            ->where('status', 1)
            ->orderBy('account_code')
            ->get();

        // Check if approver is set up for journal module
        $hasApprover = $this->hasApproverSetup('journal');

        return view('gl.journal.form', $this->withSystemSettings([
            'journal' => new gl_journal(),
            'glAccounts' => $glAccounts,
            'isEdit' => false,
            'hasApprover' => $hasApprover,
        ]));
    }

    /**
     * Store a newly created journal entry
     */
    public function store(Request $request)
    {
        $this->authorizeCreate(Modules::GL, Modules::GL_JOURNAL);

        $validated = $this->validateJournal($request);

        DB::transaction(function () use ($validated, $request) {
            $journal = gl_journal::create([
                'organization_id' => $this->getOrganizationId(),
                'journal_no' => $this->generateJournalNumber(),
                'journal_date' => $validated['journal_date'],
                'source_module' => 'manual',
                'journal_type' => $validated['journal_type'],
                'reference_type' => gl_journal::class,
                'reference_id' => 0,
                'description' => $validated['description'],
                'total_debit' => $validated['total_debit'],
                'total_credit' => $validated['total_credit'],
                'status' => 'draft',
                'approval_status' => '0',
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['lines'] as $index => $line) {
                gl_journal_line::create([
                    'gl_journal_id' => $journal->id,
                    'line_no' => $index + 1,
                    'gl_account_id' => $line['gl_account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($journal, 'journal', (float) $validated['total_debit'], Auth::id());
                $journal->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('gl.journal')->with('success', 'Journal entry saved.');
    }

    /**
     * Show the form for editing a journal entry
     */
    public function edit(gl_journal $journal)
    {
        $this->authorizeUpdate(Modules::GL, Modules::GL_JOURNAL);

        if (!in_array($journal->approval_status, ['0', '4'])) {
            abort(403, 'Only draft and returned journals can be edited.');
        }

        if ($journal->created_by != Auth::id() && Auth::user()->role_id != 1) {
            abort(403, 'You can only edit your own journal entries.');
        }

        $journal->load('lines');

        $glAccounts = chart_of_account::with('structure', 'accountType')
            ->where('organization_id', $this->getOrganizationId())
            ->whereHas('structure', fn($q) => $q->where('status', 1)->where('is_default', true))
            ->where('is_posting', true)
            ->where('status', 1)
            ->orderBy('account_code')
            ->get();

        // Properly format lines for the view
        $lines = $journal->lines->map(function ($line) {
            return [
                'id' => $line->id,
                'gl_account_id' => $line->gl_account_id,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'description' => $line->description,
            ];
        })->values()->toArray();

        // If no lines, provide default empty lines
        if (empty($lines)) {
            $lines = [
                [
                    'gl_account_id' => '',
                    'description' => '',
                    'debit' => 0,
                    'credit' => 0,
                ],
                [
                    'gl_account_id' => '',
                    'description' => '',
                    'debit' => 0,
                    'credit' => 0,
                ],
            ];
        }

        $hasApprover = $this->hasApproverSetup('journal');

        return view('gl.journal.form', $this->withSystemSettings([
            'journal' => $journal,
            'lines' => $lines,
            'glAccounts' => $glAccounts,
            'isEdit' => true,
            'hasApprover' => $hasApprover,
        ]));
    }

    /**
     * Update a journal entry
     */
    public function update(Request $request, gl_journal $journal)
    {
        $this->authorizeUpdate(Modules::GL, Modules::GL_JOURNAL);

        if (!in_array($journal->approval_status, ['0', '4'])) {
            abort(403, 'Only draft and returned journals can be edited.');
        }

        if ($journal->created_by != Auth::id() && Auth::user()->role_id != 1) {
            abort(403, 'You can only edit your own journal entries.');
        }

        $validated = $this->validateJournal($request);

        DB::transaction(function () use ($validated, $journal, $request) {
            $journal->update([
                'journal_date' => $validated['journal_date'],
                'journal_type' => $validated['journal_type'],
                'description' => $validated['description'],
                'total_debit' => $validated['total_debit'],
                'total_credit' => $validated['total_credit'],
                'updated_by' => Auth::id(),
            ]);

            $journal->lines()->delete();

            foreach ($validated['lines'] as $index => $line) {
                gl_journal_line::create([
                    'gl_journal_id' => $journal->id,
                    'line_no' => $index + 1,
                    'gl_account_id' => $line['gl_account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            if ($request->boolean('submit_for_approval')) {
                $this->approvals->submit($journal->fresh(), 'journal', (float) $validated['total_debit'], Auth::id());
                $journal->update(['submitted_at' => now(), 'submitted_by' => Auth::id()]);
            }
        });

        return redirect()->route('gl.journal')->with('success', 'Journal entry updated.');
    }

    /**
     * Show journal entry approval view
     */
    public function showApproval(gl_journal $journal, approval_transaction $transaction)
    {

        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $journal->load(['lines.glAccount', 'creator']);

        $glAccounts = chart_of_account::with('structure', 'accountType')
            ->where('organization_id', $this->getOrganizationId())
            ->whereHas('structure', fn($q) => $q->where('status', 1)->where('is_default', true))
            ->where('is_posting', true)
            ->where('status', 1)
            ->orderBy('account_code')
            ->get();

        return view('gl.journal.approve', $this->withSystemSettings([
            'journal' => $journal,
            'transaction' => $transaction,
            'step' => $step,
            'glAccounts' => $glAccounts,
        ]));
    }

    /**
     * Approve journal entry
     */
    public function approve(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::GL, Modules::GL_JOURNAL);

        $step = $transaction->currentStep();
        abort_unless($step, 404);

        $journal = $transaction->approvable;
        abort_unless($journal instanceof gl_journal, 404);

        // If step allows editing, update lines
        if ($step->can_edit_chart_of_account || $step->can_edit_amount) {
            $validated = $request->validate([
                'lines' => 'nullable|array',
                'lines.*.gl_account_id' => 'nullable|exists:chart_of_accounts,id',
                'lines.*.debit' => 'nullable|numeric|min:0',
                'lines.*.credit' => 'nullable|numeric|min:0',
                'lines.*.description' => 'nullable|string|max:255',
            ]);

            if (isset($validated['lines']) && !empty($validated['lines'])) {
                $totalDebit = 0;
                $totalCredit = 0;

                foreach ($validated['lines'] as $lineId => $lineData) {
                    $line = gl_journal_line::where('gl_journal_id', $journal->id)->findOrFail($lineId);

                    if ($step->can_edit_chart_of_account && isset($lineData['gl_account_id'])) {
                        $line->gl_account_id = $lineData['gl_account_id'];
                    }

                    if ($step->can_edit_amount) {
                        $line->debit = $lineData['debit'] ?? 0;
                        $line->credit = $lineData['credit'] ?? 0;
                    }

                    if (isset($lineData['description'])) {
                        $line->description = $lineData['description'];
                    }

                    $line->updated_by = Auth::id();
                    $line->save();

                    $totalDebit += (float) $line->debit;
                    $totalCredit += (float) $line->credit;
                }

                // Update journal totals
                $journal->update([
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'updated_by' => Auth::id(),
                ]);
                $journal->refresh();
            }
        }

        // Proceed with approval workflow
        $this->approvals->approve($transaction->fresh(), Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Journal entry approved.');
    }

    /**
     * Return journal entry to requester
     */
    public function returnJournal(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::GL, Modules::GL_JOURNAL);

        $request->validate(['remarks' => 'required|string|max:1000']);
        $this->approvals->returnToRequester($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Journal entry returned to requester.');
    }

    /**
     * Reject journal entry
     */
    public function rejectJournal(Request $request, approval_transaction $transaction)
    {
        $this->authorizeUpdate(Modules::GL, Modules::GL_JOURNAL);

        $request->validate(['remarks' => 'required|string|max:1000']);
        $this->approvals->reject($transaction, Auth::id(), $request->input('remarks'));

        return redirect()->route('approvals.index')->with('success', 'Journal entry rejected.');
    }

    /**
     * Validate journal request
     */
    private function validateJournal(Request $request): array
    {
        $validated = $request->validate([
            'journal_date' => 'required|date',
            'description' => 'required|string|max:500',
            'journal_type' => 'required|string|in:manual,adjustment,reversal',
            'lines' => 'required|array|min:2',
            'lines.*.gl_account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        // Validate each line has either debit or credit
        foreach ($validated['lines'] as $line) {
            if (($line['debit'] ?? 0) > 0 && ($line['credit'] ?? 0) > 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Each line can only have either debit or credit, not both.'
                ]);
            }
            if (($line['debit'] ?? 0) == 0 && ($line['credit'] ?? 0) == 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Each line must have either a debit or credit amount.'
                ]);
            }
        }

        $totalDebit = collect($validated['lines'])->sum('debit');
        $totalCredit = collect($validated['lines'])->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw ValidationException::withMessages([
                'lines' => 'Total debits (' . number_format($totalDebit, 2) . ') must equal total credits (' . number_format($totalCredit, 2) . ').'
            ]);
        }

        $validated['total_debit'] = $totalDebit;
        $validated['total_credit'] = $totalCredit;

        return $validated;
    }

    /**
     * Check if approver is set up for journal module
     */
    private function hasApproverSetup(string $moduleCode): bool
    {
        $workflow = approval_workflow::resolveFor($moduleCode, 0, $this->getOrganizationId());
        return $workflow && $workflow->steps()->where('is_active', true)->exists();
    }

    /**
     * Delete a journal entry (draft only)
     */
    public function destroy(gl_journal $journal)
    {
        $this->authorizeDelete(Modules::GL, Modules::GL_JOURNAL);

        if ($journal->approval_status !== '0') {
            abort(403, 'Only draft journals can be deleted.');
        }

        if ($journal->created_by != Auth::id() && Auth::user()->role_id != 1) {
            abort(403, 'You can only delete your own journal entries.');
        }

        $journal->lines()->delete();
        $journal->delete();

        return redirect()->route('gl.journals.index')->with('success', 'Journal entry deleted.');
    }
}