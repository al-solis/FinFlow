<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Facades\DB;
use App\Models\rfd_header;
use App\Models\gl_journal;
use App\Models\gl_journal_line;
use App\Models\ap_invoice;
use App\Models\ap_invoice_line;
use App\Models\ap_payment;
use App\Models\ap_payment_application;
use App\Models\vendor;
use App\Models\bank_account;
use App\Models\chart_of_account;
use App\Models\payment_method;
use App\Models\bank_reconciliation_line;
use App\Models\rfd_detail;
use App\Models\CashAdvance;
use App\Models\CashAdvanceLiquidation;
use App\Models\CashAdvanceLiquidationDetail;
use App\Models\CashAdvanceRefund;
use App\Models\ReimbursementDetail;

class AccountingService
{
    // ==================== RFD ACCOUNTING ====================

    public function postRfdApproval(rfd_header $rfd, int $actorId): gl_journal
    {
        return DB::transaction(function () use ($rfd, $actorId) {
            $details = $rfd->details()->with(['taxes.tax', 'vendor'])->get();

            if ($details->isEmpty()) {
                throw new RuntimeException('Cannot post an RFD with no lines.');
            }

            $journal = gl_journal::create([
                'organization_id' => $rfd->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => now()->toDateString(),
                'source_module' => 'rfd',
                'journal_type' => 'approval_posting',
                'reference_type' => rfd_header::class,
                'reference_id' => $rfd->id,
                'description' => 'RFD-' . str_pad($rfd->id, 6, '0', STR_PAD_LEFT) . ' approved — ' . $rfd->remarks,
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            $lineNo = 1;
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($details->groupBy('vendor_id') as $vendorId => $vendorLines) {
                $vendor = $vendorLines->first()->vendor;
                $apAccountId = $this->resolveVendorApAccount($vendor);

                $invoiceGross = 0;
                $invoiceTax = 0;
                $invoiceDiscount = 0;
                $invoiceLinesData = [];

                foreach ($vendorLines as $detail) {
                    gl_journal_line::create([
                        'gl_journal_id' => $journal->id,
                        'line_no' => $lineNo++,
                        'gl_account_id' => $detail->gl_account_id,
                        'debit' => $detail->taxable_amount,
                        'credit' => 0,
                        'description' => $detail->description,
                        'subledger_type' => rfd_detail::class,
                        'subledger_id' => $detail->id,
                        'created_by' => $actorId,
                    ]);
                    $totalDebit += $detail->taxable_amount;
                    $invoiceGross += $detail->quantity * $detail->unit_price;
                    $invoiceDiscount += $detail->discount_amount;

                    $invoiceLinesData[] = [
                        'source_line_id' => $detail->id,
                        'gl_account_id' => $detail->gl_account_id,
                        'description' => $detail->description,
                        'taxable_amount' => $detail->taxable_amount,
                        'tax_amount' => $detail->tax_amount,
                        'amount' => $detail->total_amount,
                    ];

                    foreach ($detail->taxes as $detailTax) {
                        $taxGlAccountId = $detailTax->tax->gl_account_id ?? null;
                        if (!$taxGlAccountId || $detailTax->tax_amount <= 0) {
                            continue;
                        }
                        gl_journal_line::create([
                            'gl_journal_id' => $journal->id,
                            'line_no' => $lineNo++,
                            'gl_account_id' => $taxGlAccountId,
                            'debit' => $detailTax->tax_amount,
                            'credit' => 0,
                            'description' => 'Tax: ' . ($detailTax->tax->code ?? ''),
                            'subledger_type' => rfd_detail::class,
                            'subledger_id' => $detail->id,
                            'created_by' => $actorId,
                        ]);
                        $totalDebit += $detailTax->tax_amount;
                        $invoiceTax += $detailTax->tax_amount;
                    }
                }

                $vendorTotalDue = $vendorLines->sum('total_amount');

                gl_journal_line::create([
                    'gl_journal_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'gl_account_id' => $apAccountId,
                    'debit' => 0,
                    'credit' => $vendorTotalDue,
                    'description' => 'AP — ' . $vendor->name,
                    'subledger_type' => vendor::class,
                    'subledger_id' => $vendorId,
                    'created_by' => $actorId,
                ]);
                $totalCredit += $vendorTotalDue;

                $invoice = ap_invoice::create([
                    'organization_id' => $rfd->organization_id,
                    'invoice_no' => $this->nextNumber('AP', ap_invoice::class),
                    'vendor_id' => $vendorId,
                    'source_type' => rfd_header::class,
                    'source_id' => $rfd->id,
                    'invoice_date' => now()->toDateString(),
                    'due_date' => $rfd->required_date,
                    'currency_id' => $rfd->currency_id,
                    'exchange_rate' => $rfd->exchange_rate,
                    'gross_amount' => $invoiceGross,
                    'discount_amount' => $invoiceDiscount,
                    'tax_amount' => $invoiceTax,
                    'net_amount' => $vendorTotalDue,
                    'amount_paid' => 0,
                    'amount_due' => $vendorTotalDue,
                    'status' => 'open',
                    'gl_journal_id' => $journal->id,
                    'created_by' => $actorId,
                ]);

                foreach ($invoiceLinesData as $lineData) {
                    ap_invoice_line::create([
                        'ap_invoice_id' => $invoice->id,
                        'source_line_type' => rfd_detail::class,
                        'source_line_id' => $lineData['source_line_id'],
                        'gl_account_id' => $lineData['gl_account_id'],
                        'description' => $lineData['description'],
                        'taxable_amount' => $lineData['taxable_amount'],
                        'tax_amount' => $lineData['tax_amount'],
                        'amount' => $lineData['amount'],
                    ]);
                }
            }

            $this->assertBalanced($totalDebit, $totalCredit);

            $journal->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            return $journal;
        });
    }

    // ==================== CASH ADVANCE ACCOUNTING ====================

    /**
     * Post journal entry for approved Cash Advance
     * Debit: Employee Advances (Asset - from CA gl_account_id)
     * Credit: AP/Payable (Liability) - will be paid when disbursed
     */
    public function postCashAdvanceApproval(CashAdvance $ca, int $actorId): gl_journal
    {
        return DB::transaction(function () use ($ca, $actorId) {
            // Get the GL account from the cash advance
            if (!$ca->gl_account_id) {
                throw new RuntimeException('No GL account configured for this cash advance.');
            }

            $journal = gl_journal::create([
                'organization_id' => $ca->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => now()->toDateString(),
                'source_module' => 'ca',
                'journal_type' => 'cash_advance',
                'reference_type' => CashAdvance::class,
                'reference_id' => $ca->id,
                'description' => 'Cash Advance - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT) . ' - ' . ($ca->purpose ?? ''),
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            // Debit: Employee Advances (Asset account from CA)
            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 1,
                'gl_account_id' => $ca->gl_account_id,
                'debit' => $ca->amount,
                'credit' => 0,
                'description' => 'Cash advance to ' . ($ca->employee?->getFullNameAttribute() ?? 'Employee'),
                'subledger_type' => CashAdvance::class,
                'subledger_id' => $ca->id,
                'created_by' => $actorId,
            ]);

            // Credit: AP/Payable (will be paid when disbursed)
            $apAccountId = $this->getCashAdvancePayableAccountId($ca->organization_id);

            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 2,
                'gl_account_id' => $apAccountId,
                'debit' => 0,
                'credit' => $ca->amount,
                'description' => 'Payable for cash advance - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
                'created_by' => $actorId,
            ]);

            $this->assertBalanced($ca->amount, $ca->amount);

            $journal->update([
                'total_debit' => $ca->amount,
                'total_credit' => $ca->amount,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            return $journal;
        });
    }

    /**
     * Get the Cash Advance Payable account (LIABILITY)
     */
    protected function getCashAdvancePayableAccountId(?int $organizationId): int
    {
        // Try to find a specific account for cash advance payable
        $payableAccount = chart_of_account::where('organization_id', $organizationId)
            ->whereHas('accountType', fn($q) => $q->where('code', 'LIABILITY'))
            ->where('is_posting', true)
            ->where('status', true)
            ->where(function ($q) {
                $q->where('account_name', 'LIKE', '%Cash Advance Payable%')
                    ->orWhere('account_name', 'LIKE', '%Employee Payable%');
            })
            ->first();

        if (!$payableAccount) {
            // Fallback: Get any liability account
            $payableAccount = chart_of_account::where('organization_id', $organizationId)
                ->whereHas('accountType', fn($q) => $q->where('code', 'LIABILITY'))
                ->where('is_posting', true)
                ->where('status', true)
                ->first();
        }

        if (!$payableAccount) {
            throw new RuntimeException('No liability account found for cash advance payable.');
        }

        return $payableAccount->id;
    }

    // ==================== LIQUIDATION ACCOUNTING ====================

    /**
     * Post journal entry for approved Liquidation
     * Debit: Expense accounts (from liquidation details)
     * Credit: Employee Advances (Asset - from CA gl_account_id)
     */
    public function postLiquidationApproval(CashAdvanceLiquidation $liquidation, int $actorId): gl_journal
    {
        return DB::transaction(function () use ($liquidation, $actorId) {
            $ca = $liquidation->cashAdvance;

            $details = $liquidation->details()->with('glAccount')->get();

            if ($details->isEmpty()) {
                throw new RuntimeException('Cannot post a liquidation with no details.');
            }

            if (!$ca->gl_account_id) {
                throw new RuntimeException('Cash advance has no GL account configured.');
            }

            $journal = gl_journal::create([
                'organization_id' => $liquidation->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => now()->toDateString(),
                'source_module' => 'liquidation',
                'journal_type' => 'liquidation',
                'reference_type' => CashAdvanceLiquidation::class,
                'reference_id' => $liquidation->id,
                'description' => 'Liquidation - LIQ-' . str_pad($liquidation->id, 6, '0', STR_PAD_LEFT) . ' for CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            $lineNo = 1;
            $totalDebit = 0;
            $totalCredit = 0;

            // Debit: Each expense account from liquidation details
            foreach ($details as $detail) {
                if (!$detail->gl_account_id) {
                    throw new RuntimeException('Liquidation detail missing GL account.');
                }

                gl_journal_line::create([
                    'gl_journal_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'gl_account_id' => $detail->gl_account_id,
                    'debit' => $detail->amount,
                    'credit' => 0,
                    'description' => $detail->description,
                    'subledger_type' => CashAdvanceLiquidationDetail::class,
                    'subledger_id' => $detail->id,
                    'created_by' => $actorId,
                ]);
                $totalDebit += $detail->amount;
            }

            // Credit: Employee Advances (reducing the asset - using CA's GL account)
            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => $lineNo++,
                'gl_account_id' => $ca->gl_account_id,
                'debit' => 0,
                'credit' => $liquidation->total_expenses,
                'description' => 'Reduction of cash advance - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
                'subledger_type' => CashAdvance::class,
                'subledger_id' => $ca->id,
                'created_by' => $actorId,
            ]);
            $totalCredit += $liquidation->total_expenses;

            // dd($ca->amount, $ca->gl_account_id, $ca->liquidated_amount, $liquidation->total_expenses, ($ca->amount - $ca->liquidated_amount) - $liquidation->total_expenses, $totalDebit, $totalCredit);

            // Check if there's excess (liquidation amount > CA remaining balance)
            $remainingAfterLiquidation = ($ca->amount - $ca->liquidated_amount) - $liquidation->total_expenses;

            // If there's excess, create a pending refund record
            // if ($remainingAfterLiquidation < 0) {
            //     $excess = abs($remainingAfterLiquidation);

            //     // Create a pending refund record (not posted to GL yet)
            //     // The refund will go through its own approval workflow
            //     $this->createPendingRefund($ca, $excess, $actorId);
            // }

            $this->assertBalanced($totalDebit, $totalCredit);

            // Update the cash advance liquidated amount
            $newLiquidatedAmount = $ca->liquidated_amount + $liquidation->total_expenses;
            $ca->update([
                'liquidated_amount' => $newLiquidatedAmount,
                'status' => $newLiquidatedAmount >= $ca->amount ? '5' : $ca->status,
                'approval_status' => $newLiquidatedAmount >= $ca->amount ? '5' : $ca->approval_status,
            ]);

            $journal->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            return $journal;
        });
    }

    /**
     * Create a pending refund record when liquidation has excess
     * This will go through its own approval workflow
     */
    protected function createPendingRefund(CashAdvance $ca, float $excess, int $actorId): void
    {
        // Check if a refund already exists for this excess
        $existingRefund = CashAdvanceRefund::where('cash_advance_id', $ca->id)
            ->where('type', 'refund')
            ->where('amount', $excess)
            ->whereIn('approval_status', ['0', '1']) // draft or pending
            ->first();

        if ($existingRefund) {
            return; // Refund already exists
        }

        // Create a new refund record (status: draft)
        CashAdvanceRefund::create([
            'organization_id' => $ca->organization_id,
            'cash_advance_id' => $ca->id,
            'employee_id' => $ca->employee_id,
            'amount' => $excess,
            'purpose' => 'Excess cash advance from liquidation - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
            'type' => 'refund',
            'status' => '0',
            'approval_status' => '0',
            'created_by' => $actorId,
        ]);
    }

    // ==================== REFUND ACCOUNTING ====================

    /**
     * Post journal entry for approved Refund (Employee to Company)
     * 
     * This creates a payable that will be paid through disbursement.
     * 
     * Journal Entry:
     *   Debit:  Employee Advances (Asset) - reducing the advance
     *   Credit: Refund Payable (Liability) - to be paid when disbursed
     * 
     * @param CashAdvanceRefund $refund
     * @param int $actorId
     * @return gl_journal
     * @throws RuntimeException
     */
    public function postRefundApproval(CashAdvanceRefund $refund, int $actorId): gl_journal
    {
        return DB::transaction(function () use ($refund, $actorId) {
            $ca = $refund->cashAdvance;

            if (!$ca) {
                throw new RuntimeException('Refund has no associated cash advance.');
            }

            // Use the refund's GL account if set, otherwise fallback to CA's GL account
            // $debitAccountId = $refund->gl_account_id ?? $ca->gl_account_id;

            // if (!$debitAccountId) {
            //     throw new RuntimeException('No GL account configured for this refund or its cash advance.');
            // }

            $journal = gl_journal::create([
                'organization_id' => $refund->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => now()->toDateString(),
                'source_module' => 'refund',
                'journal_type' => 'refund_approval',
                'reference_type' => CashAdvanceRefund::class,
                'reference_id' => $refund->id,
                'description' => 'Refund - REF-' . str_pad($refund->id, 6, '0', STR_PAD_LEFT) . ' for CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            // Debit: Employee Advances (reducing the asset)
            // This reduces the amount owed by the employee
            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 1,
                'gl_account_id' => $refund->gl_account_id ?? $ca->gl_account_id,
                'debit' => $refund->amount,
                'credit' => 0,
                'description' => 'Refund of excess cash advance - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT) . ' - ' . ($refund->purpose ?? ''),
                'subledger_type' => CashAdvance::class,
                'subledger_id' => $ca->id,
                'created_by' => $actorId,
            ]);

            // Credit: Payable (to be paid when disbursed)
            // This creates a liability that will be paid out later
            // $payableAccountId = $this->getRefundPayableAccountId($refund->organization_id);

            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 2,
                'gl_account_id' => $ca->gl_account_id,
                'debit' => 0,
                'credit' => $refund->amount,
                'description' => 'Refund received from employee - ' . ($refund->employee?->getFullNameAttribute() ?? 'Employee'),
                'subledger_type' => CashAdvanceRefund::class,
                'subledger_id' => $refund->id,
                'created_by' => $actorId,
            ]);

            $this->assertBalanced($refund->amount, $refund->amount);

            $journal->update([
                'total_debit' => $refund->amount,
                'total_credit' => $refund->amount,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            // Update refund status to approved
            $refund->update([
                'approval_status' => '2',
                'status' => '2',
                'approved_by' => $actorId,
            ]);

            return $journal;
        });
    }

    /**
     * Get Refund Payable account (LIABILITY)
     */
    protected function getRefundPayableAccountId(?int $organizationId): int
    {
        $payableAccount = chart_of_account::where('organization_id', $organizationId)
            ->whereHas('accountType', fn($q) => $q->where('code', 'LIABILITY'))
            ->where('is_posting', true)
            ->where('status', true)
            ->where(function ($q) {
                $q->where('account_name', 'LIKE', '%Refund Payable%')
                    ->orWhere('account_name', 'LIKE', '%Employee Payable%')
                    ->orWhere('account_name', 'LIKE', '%Due to Employee%');
            })
            ->first();

        if (!$payableAccount) {
            // Fallback: Get any liability account
            $payableAccount = chart_of_account::where('organization_id', $organizationId)
                ->whereHas('accountType', fn($q) => $q->where('code', 'LIABILITY'))
                ->where('is_posting', true)
                ->where('status', true)
                ->first();
        }

        if (!$payableAccount) {
            throw new RuntimeException('No liability account found for refund payable.');
        }

        return $payableAccount->id;
    }

    // ==================== REIMBURSEMENT ACCOUNTING ====================

    /**
     * Post journal entry for approved Reimbursement (Company to Employee)
     * 
     * This creates a payable that will be paid through disbursement.
     * 
     * Journal Entry:
     *   Debit:  Expense accounts (from reimbursement details)
     *   Credit: Reimbursement Payable (Liability) - to be paid when disbursed
     * 
     * @param CashAdvanceRefund $reimbursement
     * @param int $actorId
     * @return gl_journal
     * @throws RuntimeException
     */
    public function postReimbursementApproval(CashAdvanceRefund $reimbursement, int $actorId): gl_journal
    {
        return DB::transaction(function () use ($reimbursement, $actorId) {
            $details = ReimbursementDetail::where('reimbursement_id', $reimbursement->id)
                ->with('glAccount')
                ->get();

            if ($details->isEmpty()) {
                throw new RuntimeException('Cannot post a reimbursement with no details.');
            }

            $journal = gl_journal::create([
                'organization_id' => $reimbursement->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => now()->toDateString(),
                'source_module' => 'reimbursement',
                'journal_type' => 'reimbursement_approval',
                'reference_type' => CashAdvanceRefund::class,
                'reference_id' => $reimbursement->id,
                'description' => 'Reimbursement - REIM-' . str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT) . ' - ' . ($reimbursement->purpose ?? ''),
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            $lineNo = 1;
            $totalDebit = 0;
            $totalCredit = 0;

            // Debit: Each expense account from reimbursement details
            foreach ($details as $detail) {
                if (!$detail->gl_account_id) {
                    throw new RuntimeException('Reimbursement detail missing GL account.');
                }

                gl_journal_line::create([
                    'gl_journal_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'gl_account_id' => $detail->gl_account_id,
                    'debit' => $detail->amount,
                    'credit' => 0,
                    'description' => $detail->description . ($detail->reference ? ' (Ref: ' . $detail->reference . ')' : ''),
                    'subledger_type' => ReimbursementDetail::class,
                    'subledger_id' => $detail->id,
                    'created_by' => $actorId,
                ]);
                $totalDebit += $detail->amount;
            }

            // Credit: Payable (to be paid when disbursed)
            $payableAccountId = $this->getReimbursementPayableAccountId($reimbursement->organization_id);

            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => $lineNo++,
                'gl_account_id' => $payableAccountId,
                'debit' => 0,
                'credit' => $reimbursement->amount,
                'description' => 'Reimbursement payable to employee - ' . ($reimbursement->employee?->getFullNameAttribute() ?? 'Employee'),
                'subledger_type' => CashAdvanceRefund::class,
                'subledger_id' => $reimbursement->id,
                'created_by' => $actorId,
            ]);
            $totalCredit += $reimbursement->amount;

            $this->assertBalanced($totalDebit, $totalCredit);

            $journal->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            // Update reimbursement status to approved
            $reimbursement->update([
                'approval_status' => '2',
                'status' => '2',
                'approved_by' => $actorId,
            ]);

            return $journal;
        });
    }

    /**
     * Get Reimbursement Payable account (LIABILITY)
     */
    protected function getReimbursementPayableAccountId(?int $organizationId): int
    {
        $payableAccount = chart_of_account::where('organization_id', $organizationId)
            ->whereHas('accountType', fn($q) => $q->where('code', 'LIABILITY'))
            ->where('is_posting', true)
            ->where('status', true)
            ->where(function ($q) {
                $q->where('account_name', 'LIKE', '%Reimbursement Payable%')
                    ->orWhere('account_name', 'LIKE', '%Employee Payable%')
                    ->orWhere('account_name', 'LIKE', '%Due to Employee%');
            })
            ->first();

        if (!$payableAccount) {
            // Fallback: Get any liability account
            $payableAccount = chart_of_account::where('organization_id', $organizationId)
                ->whereHas('accountType', fn($q) => $q->where('code', 'LIABILITY'))
                ->where('is_posting', true)
                ->where('status', true)
                ->first();
        }

        if (!$payableAccount) {
            throw new RuntimeException('No liability account found for reimbursement payable.');
        }

        return $payableAccount->id;
    }

    // ==================== DISBURSEMENT ACCOUNTING ====================

    /**
     * Settle payable accounts (Cash Advance, Refund, Reimbursement)
     * Debit: Payable (Liability)
     * Credit: Cash/Bank (Asset)
     */
    public function postDisbursement(
        array $payableIds,
        string $payableType, // 'ca', 'refund', 'reimbursement'
        int $bankAccountId,
        int $paymentMethodId,
        float $amountToPay,
        string $referenceNumber,
        ?string $checkDate,
        int $actorId,
    ): ap_payment {
        return DB::transaction(function () use ($payableIds, $payableType, $bankAccountId, $paymentMethodId, $amountToPay, $referenceNumber, $checkDate, $actorId) {

            // Get the payable records based on type
            $payables = $this->getPayableRecords($payableIds, $payableType);

            if ($payables->isEmpty()) {
                throw new RuntimeException('No payables selected for disbursement.');
            }

            $paymentMethod = payment_method::findOrFail($paymentMethodId);

            if ($paymentMethod->requires_bank && !$bankAccountId) {
                throw new RuntimeException("Payment method '{$paymentMethod->name}' requires a bank/cash account.");
            }
            if ($paymentMethod->requires_reference_no && !trim((string) $referenceNumber)) {
                throw new RuntimeException("Payment method '{$paymentMethod->name}' requires a reference number.");
            }
            if ($paymentMethod->requires_check && !$checkDate) {
                throw new RuntimeException("Payment method '{$paymentMethod->name}' requires a check date.");
            }

            $bankAccount = bank_account::findOrFail($bankAccountId);
            if (!$bankAccount->chart_of_account_id) {
                throw new RuntimeException('Selected bank/cash account has no linked GL account.');
            }

            // Get the payable account ID for credit
            $payableAccountId = $this->getPayableAccountId($payableType, $bankAccount->organization_id);

            $journal = gl_journal::create([
                'organization_id' => $bankAccount->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => now()->toDateString(),
                'source_module' => 'disbursement',
                'journal_type' => 'payable_disbursement',
                'reference_type' => $payableType === 'ca' ? CashAdvance::class : CashAdvanceRefund::class,
                'reference_id' => 0,
                'description' => 'Payable disbursement — ' . $referenceNumber,
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            // Debit: Payable account (reducing the liability)
            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 1,
                'gl_account_id' => $payableAccountId,
                'debit' => $amountToPay,
                'credit' => 0,
                'description' => 'Payment of ' . $payableType . ' payable',
                'created_by' => $actorId,
            ]);

            // Credit: Cash/Bank account
            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 2,
                'gl_account_id' => $bankAccount->chart_of_account_id,
                'debit' => 0,
                'credit' => $amountToPay,
                'description' => 'Disbursement — ' . $referenceNumber,
                'created_by' => $actorId,
            ]);

            $this->assertBalanced($amountToPay, $amountToPay);

            // Create payment record
            $payment = ap_payment::create([
                'organization_id' => $bankAccount->organization_id,
                'payment_no' => $this->nextNumber('PV', ap_payment::class),
                'vendor_id' => null, // Not vendor-specific for employee payables
                'payment_date' => now()->toDateString(),
                'bank_account_id' => $bankAccount->id,
                'payment_method_id' => $paymentMethod->id,
                'reference_number' => $referenceNumber,
                'check_date' => $checkDate,
                'disbursement_method' => $paymentMethod->name,
                'currency_id' => $bankAccount->currency_id,
                'exchange_rate' => 1,
                'total_amount' => $amountToPay,
                'status' => 'posted',
                'gl_journal_id' => $journal->id,
                'created_by' => $actorId,
            ]);

            $journal->update([
                'reference_id' => $payment->id,
                'total_debit' => $amountToPay,
                'total_credit' => $amountToPay,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            // Mark payables as paid
            $this->markPayablesAsPaid($payables, $actorId);

            return $payment;
        });
    }

    /**
     * Get payable records based on type
     */
    protected function getPayableRecords(array $payableIds, string $payableType)
    {
        return match ($payableType) {
            'ca' => CashAdvance::whereIn('id', $payableIds)
                ->where('approval_status', '2')
                ->where('status', '!=', '5')
                ->get(),
            'refund' => CashAdvanceRefund::whereIn('id', $payableIds)
                ->where('type', 'refund')
                ->where('approval_status', '2')
                ->where('status', '!=', '5')
                ->get(),
            'reimbursement' => CashAdvanceRefund::whereIn('id', $payableIds)
                ->where('type', 'reimbursement')
                ->where('approval_status', '2')
                ->where('status', '!=', '5')
                ->get(),
            default => throw new RuntimeException('Invalid payable type.'),
        };
    }

    /**
     * Get payable account ID based on type
     */
    protected function getPayableAccountId(string $payableType, ?int $organizationId): int
    {
        return match ($payableType) {
            'ca' => $this->getCashAdvancePayableAccountId($organizationId),
            'refund' => $this->getRefundPayableAccountId($organizationId),
            'reimbursement' => $this->getReimbursementPayableAccountId($organizationId),
            default => throw new RuntimeException('Invalid payable type.'),
        };
    }

    /**
     * Mark payables as paid
     */
    protected function markPayablesAsPaid($payables, int $actorId): void
    {
        foreach ($payables as $payable) {
            $payable->update([
                'status' => '5',
                'paid_at' => now(),
                'paid_by' => $actorId,
            ]);
        }
    }

    // ==================== HELPER METHODS ====================

    protected function resolveVendorApAccount(vendor $vendor): int
    {
        if ($vendor->default_ap_chart_of_account_id) {
            return $vendor->default_ap_chart_of_account_id;
        }

        $candidates = chart_of_account::where('organization_id', $vendor->organization_id)
            ->where('main_account_id', $vendor->ap_account_id)
            ->where('is_posting', true)
            ->where('status', true)
            ->get();

        if ($candidates->count() === 1) {
            return $candidates->first()->id;
        }

        throw new RuntimeException(
            $candidates->isEmpty()
            ? "Vendor '{$vendor->name}' has no posting AP account configured. Set vendors.default_ap_chart_of_account_id."
            : "Vendor '{$vendor->name}' has {$candidates->count()} possible AP accounts and no default is set — cannot post automatically. Set vendors.default_ap_chart_of_account_id."
        );
    }

    protected function markRfdDisbursed(int $rfdId, int $actorId): void
    {
        $rfd = rfd_header::find($rfdId);
        if (!$rfd) {
            return;
        }
        $allPaid = $rfd->apInvoices()->where('status', '!=', 'paid')->doesntExist();

        $rfd->forceFill([
            'payment_status' => $allPaid ? 1 : 2,
            'disbursed_at' => now(),
            'disbursed_by' => $actorId,
        ])->save();
    }

    public function postBankStatementLine(
        bank_account $bankAccount,
        bank_reconciliation_line $line,
        int $offsetGlAccountId,
        int $actorId
    ): gl_journal {
        return DB::transaction(function () use ($bankAccount, $line, $offsetGlAccountId, $actorId) {
            if (!$bankAccount->chart_of_account_id) {
                throw new RuntimeException('Bank account has no linked GL account.');
            }

            $amount = (float) $line->amount;
            $isDeposit = $amount >= 0;
            $absAmount = abs($amount);

            if ($absAmount <= 0) {
                throw new RuntimeException("Line #{$line->line_no} has a zero amount and cannot be posted.");
            }

            $description = trim(($line->description ?: 'Bank statement item') . ($line->reference ? ' — Ref ' . $line->reference : ''));

            $journal = gl_journal::create([
                'organization_id' => $bankAccount->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => $line->transaction_date,
                'source_module' => 'bank_reconciliation',
                'journal_type' => 'bank_statement_import',
                'reference_type' => bank_reconciliation_line::class,
                'reference_id' => $line->id,
                'description' => $description,
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 1,
                'gl_account_id' => $isDeposit ? $bankAccount->chart_of_account_id : $offsetGlAccountId,
                'debit' => $absAmount,
                'credit' => 0,
                'description' => $description,
                'created_by' => $actorId,
            ]);

            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 2,
                'gl_account_id' => $isDeposit ? $offsetGlAccountId : $bankAccount->chart_of_account_id,
                'debit' => 0,
                'credit' => $absAmount,
                'description' => $description,
                'created_by' => $actorId,
            ]);

            $this->assertBalanced($absAmount, $absAmount);

            $journal->update([
                'total_debit' => $absAmount,
                'total_credit' => $absAmount,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            return $journal;
        });
    }

    protected function assertBalanced(float $debit, float $credit): void
    {
        if (round($debit - $credit, 2) !== 0.0) {
            throw new RuntimeException("Journal is not balanced: debit {$debit} vs credit {$credit}.");
        }
    }

    protected function nextNumber(string $prefix, string $model): string
    {
        $date = now()->format('Ymd');
        $count = $model::whereDate('created_at', now()->toDateString())->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}