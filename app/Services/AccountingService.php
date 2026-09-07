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
use App\Models\ap_payment as ApPayment;
use App\Services\EmployeeVendorService;

class AccountingService
{
    protected EmployeeVendorService $employeeVendorService;

    public function __construct()
    {
        $this->employeeVendorService = new EmployeeVendorService();
    }

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
                'approval_status' => '2',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            return $journal;
        });
    }

    // ==================== CASH ADVANCE ACCOUNTING ====================

    /**
     * Cash Advance approval - just updates status, no GL entry yet
     * GL entry will be created when disbursed
     */
    public function postCashAdvanceApproval(CashAdvance $ca, int $actorId): void
    {
        // No GL entry - just update status
        $ca->update([
            'approval_status' => '2',
            'status' => '2',
            'approved_by' => $actorId,
        ]);
    }

    /**
     * Disburse approved Cash Advance
     * Creates GL entry: Dr: Employee Advances (Asset), Cr: Cash/Bank (Asset)
     * Creates AP Invoice and AP Payment for the employee vendor
     */
    public function disburseCashAdvance(
        CashAdvance $ca,
        int $bankAccountId,
        int $paymentMethodId,
        float $amountToPay,
        string $referenceNumber,
        ?string $checkDate,
        int $actorId
    ): ap_payment {
        return DB::transaction(function () use ($ca, $bankAccountId, $paymentMethodId, $amountToPay, $referenceNumber, $checkDate, $actorId) {
            // Validate CA is approved and not fully disbursed
            if ($ca->approval_status !== '2') {
                throw new RuntimeException('Cash Advance must be approved before disbursement.');
            }
            if ($ca->isFullyDisbursed()) {
                throw new RuntimeException('Cash Advance has already been fully disbursed.');
            }

            $bankAccount = bank_account::findOrFail($bankAccountId);
            if (!$bankAccount->chart_of_account_id) {
                throw new RuntimeException('Selected bank/cash account has no linked GL account.');
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

            // Get or create employee vendor
            $employee = $ca->employee;
            if (!$employee) {
                throw new RuntimeException('Cash advance has no employee assigned.');
            }

            $vendor = $this->employeeVendorService->getOrCreateEmployeeVendor($employee);
            if (!$vendor->default_ap_chart_of_account_id) {
                throw new RuntimeException("Employee vendor '{$vendor->name}' has no AP account configured.");
            }

            // Calculate remaining amount
            $remainingAmount = $ca->remaining_amount;
            $amountToPay = min($amountToPay, $remainingAmount);

            if ($amountToPay <= 0) {
                throw new RuntimeException('Amount to pay must be greater than zero.');
            }

            // Create GL Journal
            $journal = gl_journal::create([
                'organization_id' => $ca->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => now()->toDateString(),
                'source_module' => 'ca_disbursement',
                'journal_type' => 'cash_advance_disbursement',
                'reference_type' => CashAdvance::class,
                'reference_id' => $ca->id,
                'description' => 'Cash Advance Disbursement - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT) . ' - ' . $referenceNumber,
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            // Debit: Employee Advances (Asset account from CA)
            if (!$ca->gl_account_id) {
                throw new RuntimeException('Cash Advance has no GL account configured.');
            }

            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 1,
                'gl_account_id' => $ca->gl_account_id,
                'debit' => $amountToPay,
                'credit' => 0,
                'description' => 'Cash advance to ' . ($employee->getFullNameAttribute() ?? $employee->name ?? 'Employee'),
                'subledger_type' => CashAdvance::class,
                'subledger_id' => $ca->id,
                'created_by' => $actorId,
            ]);

            // Credit: Cash/Bank account
            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 2,
                'gl_account_id' => $bankAccount->chart_of_account_id,
                'debit' => 0,
                'credit' => $amountToPay,
                'description' => 'Cash disbursement - ' . $referenceNumber,
                'created_by' => $actorId,
            ]);

            $this->assertBalanced($amountToPay, $amountToPay);

            $journal->update([
                'total_debit' => $amountToPay,
                'total_credit' => $amountToPay,
                'status' => 'posted',
                'approval_status' => '2',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            // Create AP Invoice for the employee vendor
            $invoice = ap_invoice::create([
                'organization_id' => $ca->organization_id,
                'invoice_no' => $this->nextNumber('AP', ap_invoice::class),
                'vendor_id' => $vendor->id,
                'source_type' => CashAdvance::class,
                'source_id' => $ca->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'currency_id' => 1,
                'exchange_rate' => 1,
                'gross_amount' => $amountToPay,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'net_amount' => $amountToPay,
                'amount_paid' => $amountToPay,
                'amount_due' => 0,
                'status' => 'paid',
                'gl_journal_id' => $journal->id,
                'created_by' => $actorId,
                'remarks' => 'Cash Advance - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT) . ' - ' . $referenceNumber,
            ]);

            // Create AP Invoice Line
            ap_invoice_line::create([
                'ap_invoice_id' => $invoice->id,
                'gl_account_id' => $ca->gl_account_id,
                'description' => 'Cash Advance - ' . ($ca->purpose ?? ''),
                'taxable_amount' => $amountToPay,
                'tax_amount' => 0,
                'amount' => $amountToPay,
            ]);

            // Create AP Payment
            $payment = ap_payment::create([
                'organization_id' => $ca->organization_id,
                'payment_no' => $this->nextNumber('PV', ap_payment::class),
                'vendor_id' => $vendor->id,
                'payment_date' => now()->toDateString(),
                'bank_account_id' => $bankAccount->id,
                'payment_method_id' => $paymentMethod->id,
                'reference_number' => $referenceNumber,
                'check_date' => $checkDate ?? null,
                'disbursement_method' => $paymentMethod->name,
                'currency_id' => 1,
                'exchange_rate' => 1,
                'total_amount' => $amountToPay,
                'status' => 'posted',
                'gl_journal_id' => $journal->id,
                'created_by' => $actorId,
                'remarks' => 'Cash Advance disbursement - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
            ]);

            // Apply payment to invoice
            ap_payment_application::create([
                'ap_payment_id' => $payment->id,
                'ap_invoice_id' => $invoice->id,
                'amount_applied' => $amountToPay,
            ]);

            // Update Cash Advance
            $totalDisbursed = ($ca->disbursed_amount ?? 0) + $amountToPay;

            $ca->update([
                'disbursed_amount' => ($ca->disbursed_amount ?? 0) + $amountToPay,
                'status' => $totalDisbursed = ($ca->amount ?? 0) ? '5' : '2',
                'updated_by' => $actorId,
            ]);

            return $payment;
        });
    }

    // ==================== LIQUIDATION ACCOUNTING ====================

    /**
     * Post journal entry for approved Liquidation
     * Debit: Expense accounts (from liquidation details)
     * Credit: Employee Advances (Asset - from CA gl_account_id)
     * Credit: Reimbursement Payable (Liability - for excess amount, if any)
     */
    // public function postLiquidationApproval(CashAdvanceLiquidation $liquidation, int $actorId): gl_journal
    // {
    //     return DB::transaction(function () use ($liquidation, $actorId) {
    //         $ca = $liquidation->cashAdvance;

    //         $details = $liquidation->details()->with('glAccount')->get();

    //         if ($details->isEmpty()) {
    //             throw new RuntimeException('Cannot post a liquidation with no details.');
    //         }

    //         if (!$ca->gl_account_id) {
    //             throw new RuntimeException('Cash advance has no GL account configured.');
    //         }

    //         $journal = gl_journal::create([
    //             'organization_id' => $liquidation->organization_id,
    //             'journal_no' => $this->nextNumber('GJ', gl_journal::class),
    //             'journal_date' => now()->toDateString(),
    //             'source_module' => 'liquidation',
    //             'journal_type' => 'liquidation',
    //             'reference_type' => CashAdvanceLiquidation::class,
    //             'reference_id' => $liquidation->id,
    //             'description' => 'Liquidation - LIQ-' . str_pad($liquidation->id, 6, '0', STR_PAD_LEFT) . ' for CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
    //             'status' => 'draft',
    //             'created_by' => $actorId,
    //         ]);

    //         $lineNo = 1;
    //         $totalDebit = 0;
    //         $totalCredit = 0;

    //         // Debit: Each expense account from liquidation details
    //         foreach ($details as $detail) {
    //             if (!$detail->gl_account_id) {
    //                 throw new RuntimeException('Liquidation detail missing GL account.');
    //             }

    //             gl_journal_line::create([
    //                 'gl_journal_id' => $journal->id,
    //                 'line_no' => $lineNo++,
    //                 'gl_account_id' => $detail->gl_account_id,
    //                 'debit' => $detail->amount,
    //                 'credit' => 0,
    //                 'description' => $detail->description,
    //                 'subledger_type' => CashAdvanceLiquidationDetail::class,
    //                 'subledger_id' => $detail->id,
    //                 'created_by' => $actorId,
    //             ]);
    //             $totalDebit += $detail->amount;
    //         }

    //         // Credit: Employee Advances (reducing the asset - using CA's GL account)
    //         gl_journal_line::create([
    //             'gl_journal_id' => $journal->id,
    //             'line_no' => $lineNo++,
    //             'gl_account_id' => $ca->gl_account_id,
    //             'debit' => 0,
    //             'credit' => $liquidation->total_expenses,
    //             'description' => 'Reduction of cash advance - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
    //             'subledger_type' => CashAdvance::class,
    //             'subledger_id' => $ca->id,
    //             'created_by' => $actorId,
    //         ]);
    //         $totalCredit += $liquidation->total_expenses;

    //         $this->assertBalanced($totalDebit, $totalCredit);

    //         // Update the cash advance liquidated amount
    //         $newLiquidatedAmount = $ca->liquidated_amount + $liquidation->total_expenses;
    //         $ca->update([
    //             'liquidated_amount' => $newLiquidatedAmount,
    //             'status' => $newLiquidatedAmount >= $ca->disbursed_amount ? '6' : $ca->status,
    //             'approval_status' => $newLiquidatedAmount >= $ca->disbursed_amount ? '6' : $ca->approval_status,
    //         ]);

    //         $journal->update([
    //             'total_debit' => $totalDebit,
    //             'total_credit' => $totalCredit,
    //             'status' => 'posted',
    //             'posted_at' => now(),
    //             'posted_by' => $actorId,
    //         ]);

    //         return $journal;
    //     });
    // }

    // ==================== REFUND ACCOUNTING ====================


    /**
     * Post journal entry for approved Liquidation
     * Debit: Expense accounts (from liquidation details)
     * Credit: Employee Advances (Asset - for covered amount)
     * Credit: Reimbursement Payable (Liability - for excess amount)
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

            // Calculate available balance before this liquidation
            $availableBalance = (float) $ca->disbursed_amount - (float) $ca->liquidated_amount;
            $excessAmount = max(0, $liquidation->total_expenses - $availableBalance);
            $coveredAmount = $liquidation->total_expenses - $excessAmount;

            //dd($availableBalance, $liquidation->total_expenses, $coveredAmount, $excessAmount, $ca->gl_account_id);

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

            // Credit Part 1: Employee Advances (reducing the asset) - for covered amount
            if ($coveredAmount > 0) {
                gl_journal_line::create([
                    'gl_journal_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'gl_account_id' => $ca->gl_account_id,
                    'debit' => 0,
                    'credit' => $coveredAmount,
                    'description' => 'Reduction of cash advance - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
                    'subledger_type' => CashAdvance::class,
                    'subledger_id' => $ca->id,
                    'created_by' => $actorId,
                ]);
                $totalCredit += $coveredAmount;
            }

            // Credit Part 2: Reimbursement Payable (Liability) - for excess amount
            if ($excessAmount > 0) {
                // Get the credit account from the liquidation record
                // If not set, fallback to default reimbursement payable
                $creditAccountId = $liquidation->credit_account_id ?? $this->getReimbursementPayableAccountId($liquidation->organization_id);

                gl_journal_line::create([
                    'gl_journal_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'gl_account_id' => $creditAccountId,
                    'debit' => 0,
                    'credit' => $excessAmount,
                    'description' => 'Reimbursement payable for excess liquidation - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
                    'subledger_type' => CashAdvanceLiquidation::class,
                    'subledger_id' => $liquidation->id,
                    'created_by' => $actorId,
                ]);
                $totalCredit += $excessAmount;

                // Create a pending reimbursement record (draft)
                $this->createPendingReimbursement($liquidation, $ca, $excessAmount, $creditAccountId, $actorId);
            }

            $this->assertBalanced($totalDebit, $totalCredit);

            // Update the cash advance liquidated amount (only the covered portion)
            $newLiquidatedAmount = $ca->liquidated_amount + $coveredAmount;
            $ca->update([
                'liquidated_amount' => $newLiquidatedAmount,
                'status' => $newLiquidatedAmount >= $ca->disbursed_amount ? '6' : $ca->status,
                'approval_status' => $newLiquidatedAmount >= $ca->disbursed_amount ? '6' : $ca->approval_status,
            ]);

            $journal->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'posted',
                'approval_status' => '2',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            return $journal;
        });
    }

    /**
     * Create a pending reimbursement record when liquidation has excess
     */
    protected function createPendingReimbursement(CashAdvanceLiquidation $liquidation, CashAdvance $ca, float $excessAmount, int $creditAccountId, int $actorId): void
    {
        // Check if a reimbursement already exists for this liquidation
        $existingReimbursement = CashAdvanceRefund::where('liquidation_id', $liquidation->id)
            ->where('type', 'reimbursement')
            ->first();

        if ($existingReimbursement) {
            return; // Reimbursement already exists
        }

        // Create a new reimbursement record (status: draft)
        $reimbursement = CashAdvanceRefund::create([
            'organization_id' => $ca->organization_id,
            'cash_advance_id' => $ca->id,
            'liquidation_id' => $liquidation->id,
            'employee_id' => $ca->employee_id,
            'amount' => $excessAmount,
            'purpose' => 'Excess liquidation from LIQ-' . str_pad($liquidation->id, 6, '0', STR_PAD_LEFT) . ' for CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
            'gl_account_id' => $creditAccountId,
            'type' => 'reimbursement',
            'status' => '0',
            'approval_status' => '0',
            'created_by' => $actorId,
        ]);

        // Copy the expense details from liquidation to reimbursement (proportional)
        $details = $liquidation->details()->get();
        $ratio = $excessAmount / $liquidation->total_expenses;

        foreach ($details as $detail) {
            ReimbursementDetail::create([
                'reimbursement_id' => $reimbursement->id,
                'expense_date' => $detail->expense_date,
                'description' => $detail->description . ' (Excess portion)',
                'gl_account_id' => $detail->gl_account_id,
                'amount' => round($detail->amount * $ratio, 2),
                'reference' => $detail->reference,
                'created_by' => $actorId,
            ]);
        }
    }

    /**
     * Disburse approved Reimbursement
     * Debit: Reimbursement Payable (Liability)
     * Credit: Cash/Bank (Asset)
     */
    public function disburseReimbursement(
        CashAdvanceRefund $reimbursement,
        int $bankAccountId,
        int $paymentMethodId,
        float $amountToPay,
        string $referenceNumber,
        ?string $checkDate,
        int $actorId
    ): ap_payment {
        return DB::transaction(function () use ($reimbursement, $bankAccountId, $paymentMethodId, $amountToPay, $referenceNumber, $checkDate, $actorId) {
            // Validate reimbursement is approved and not fully paid
            if ($reimbursement->approval_status !== '2') {
                throw new RuntimeException('Reimbursement must be approved before disbursement.');
            }
            if ($reimbursement->isPaid()) {
                throw new RuntimeException('Reimbursement has already been paid.');
            }

            $bankAccount = bank_account::findOrFail($bankAccountId);
            if (!$bankAccount->chart_of_account_id) {
                throw new RuntimeException('Selected bank/cash account has no linked GL account.');
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

            // Get or create employee vendor
            $employee = $reimbursement->employee;
            if (!$employee) {
                throw new RuntimeException('Cash advance has no employee assigned.');
            }

            $vendor = $this->employeeVendorService->getOrCreateEmployeeVendor($employee);
            if (!$vendor->default_ap_chart_of_account_id) {
                throw new RuntimeException("Employee vendor '{$vendor->name}' has no AP account configured.");
            }

            // Get the reimbursement payable account (credit account)
            $payableAccountId = $reimbursement->gl_account_id ?? $this->getReimbursementPayableAccountId($reimbursement->organization_id);

            $journal = gl_journal::create([
                'organization_id' => $reimbursement->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => now()->toDateString(),
                'source_module' => 'reimbursement_disbursement',
                'journal_type' => 'reimbursement_payment',
                'reference_type' => CashAdvanceRefund::class,
                'reference_id' => $reimbursement->id,
                'description' => 'Reimbursement payment - REIM-' . str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT) . ' - ' . $referenceNumber,
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            // Debit: Reimbursement Payable (reducing the liability)
            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 1,
                'gl_account_id' => $payableAccountId,
                'debit' => $amountToPay,
                'credit' => 0,
                'description' => 'Payment of reimbursement to ' . ($reimbursement->employee?->getFullNameAttribute() ?? 'Employee'),
                'subledger_type' => CashAdvanceRefund::class,
                'subledger_id' => $reimbursement->id,
                'created_by' => $actorId,
            ]);

            // Credit: Cash/Bank account
            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 2,
                'gl_account_id' => $bankAccount->chart_of_account_id,
                'debit' => 0,
                'credit' => $amountToPay,
                'description' => 'Disbursement - ' . $referenceNumber,
                'created_by' => $actorId,
            ]);

            $this->assertBalanced($amountToPay, $amountToPay);

            $journal->update([
                'total_debit' => $amountToPay,
                'total_credit' => $amountToPay,
                'status' => 'posted',
                'approval_status' => '2',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            // Mark reimbursement as paid
            $reimbursement->update([
                'status' => $reimbursement->amount == $reimbursement->totalReimbursementDisbursedAmount() ? '5' : $reimbursement->status,
                'paid_at' => now(),
                'paid_by' => $actorId,
            ]);

            // Create payment record (for tracking)
            $payment = ap_payment::create([
                'organization_id' => $reimbursement->organization_id,
                'payment_no' => $this->nextNumber('PV', ap_payment::class),
                'vendor_id' => $vendor->id,
                'payment_date' => now()->toDateString(),
                'bank_account_id' => $bankAccount->id,
                'payment_method_id' => $paymentMethod->id,
                'reference_number' => $referenceNumber,
                'check_date' => $checkDate,
                'disbursement_method' => $paymentMethod->name,
                'currency_id' => 1,
                'exchange_rate' => 1,
                'total_amount' => $amountToPay,
                'status' => 'posted',
                'gl_journal_id' => $journal->id,
                'created_by' => $actorId,
                'remarks' => 'Reimbursement payment - REIM-' . str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT),
            ]);

            return $payment;
        });
    }


    /**
     * Post journal entry for approved Refund (Employee to Company)
     * Debit: Cash In Bank (Asset) - to be received when disbursed
     * Credit: Employee Advances (Asset) - reducing the asset
     */
    public function postRefundApproval(CashAdvanceRefund $refund, int $actorId): gl_journal
    {
        return DB::transaction(function () use ($refund, $actorId) {
            $ca = $refund->cashAdvance;

            if (!$ca) {
                throw new RuntimeException('Refund has no associated cash advance.');
            }

            if (!$ca->gl_account_id) {
                throw new RuntimeException('Cash advance has no GL account configured.');
            }

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

            // Debit: Cash In Bank
            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 1,
                'gl_account_id' => $refund->gl_account_id, // Cash/Bank account for refund
                'debit' => $refund->amount,
                'credit' => 0,
                'description' => 'Refund of excess cash advance - CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT) . ' - ' . ($refund->purpose ?? ''),
                'subledger_type' => CashAdvance::class,
                'subledger_id' => $ca->id,
                'created_by' => $actorId,
            ]);

            // Credit: Employee Advances (reducing the asset)
            // $payableAccountId = $this->getRefundPayableAccountId($refund->organization_id);

            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 2,
                'gl_account_id' => $ca->gl_account_id, //$payableAccountId,
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
                'approval_status' => '2',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            // Update refund status to approved
            $refund->update([
                'approval_status' => '2',
                'status' => '2',
                'approved_by' => $actorId,
            ]);

            $ca->update([
                'liquidated_amount' => $ca->liquidated_amount + $refund->amount,
                'status' => $ca->liquidated_amount + $refund->amount >= $ca->amount ? '6' : $ca->status,
                'updated_by' => $actorId,
                'updated_at' => now(),
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
     * Summary of postReimbursementApproval
     * No journal entry is created for reimbursement approval. The reimbursement status is simply updated to approved.
     */
    public function postReimbursementApproval(CashAdvanceRefund $reimbursement, int $actorId)
    {
        // Update reimbursement status to approved
        $reimbursement->update([
            'approval_status' => '2',
            'status' => '2',
            'approved_by' => $actorId,
        ]);
    }

    /**
     * Post journal entry for approved Reimbursement (Company to Employee)
     * Debit: Expense accounts (from reimbursement details)
     * Credit: Reimbursement Payable (Liability) - to be paid when disbursed
     */
    //Original code that create entries
    // public function postReimbursementApproval(CashAdvanceRefund $reimbursement, int $actorId): gl_journal
    // {
    //     return DB::transaction(function () use ($reimbursement, $actorId) {
    //         $details = ReimbursementDetail::where('reimbursement_id', $reimbursement->id)
    //             ->with('glAccount')
    //             ->get();

    //         if ($details->isEmpty()) {
    //             throw new RuntimeException('Cannot post a reimbursement with no details.');
    //         }

    //         $journal = gl_journal::create([
    //             'organization_id' => $reimbursement->organization_id,
    //             'journal_no' => $this->nextNumber('GJ', gl_journal::class),
    //             'journal_date' => now()->toDateString(),
    //             'source_module' => 'reimbursement',
    //             'journal_type' => 'reimbursement_approval',
    //             'reference_type' => CashAdvanceRefund::class,
    //             'reference_id' => $reimbursement->id,
    //             'description' => 'Reimbursement - REIM-' . str_pad($reimbursement->id, 6, '0', STR_PAD_LEFT) . ' - ' . ($reimbursement->purpose ?? ''),
    //             'status' => 'draft',
    //             'created_by' => $actorId,
    //         ]);

    //         $lineNo = 1;
    //         $totalDebit = 0;
    //         $totalCredit = 0;

    //         // Debit: Each expense account from reimbursement details
    //         foreach ($details as $detail) {
    //             if (!$detail->gl_account_id) {
    //                 throw new RuntimeException('Reimbursement detail missing GL account.');
    //             }

    //             gl_journal_line::create([
    //                 'gl_journal_id' => $journal->id,
    //                 'line_no' => $lineNo++,
    //                 'gl_account_id' => $detail->gl_account_id,
    //                 'debit' => $detail->amount,
    //                 'credit' => 0,
    //                 'description' => $detail->description . ($detail->reference ? ' (Ref: ' . $detail->reference . ')' : ''),
    //                 'subledger_type' => ReimbursementDetail::class,
    //                 'subledger_id' => $detail->id,
    //                 'created_by' => $actorId,
    //             ]);
    //             $totalDebit += $detail->amount;
    //         }

    //         // Credit: Payable (to be paid when disbursed)
    //         $payableAccountId = $this->getReimbursementPayableAccountId($reimbursement->organization_id);

    //         gl_journal_line::create([
    //             'gl_journal_id' => $journal->id,
    //             'line_no' => $lineNo++,
    //             'gl_account_id' => $payableAccountId,
    //             'debit' => 0,
    //             'credit' => $reimbursement->amount,
    //             'description' => 'Reimbursement payable to employee - ' . ($reimbursement->employee?->getFullNameAttribute() ?? 'Employee'),
    //             'subledger_type' => CashAdvanceRefund::class,
    //             'subledger_id' => $reimbursement->id,
    //             'created_by' => $actorId,
    //         ]);
    //         $totalCredit += $reimbursement->amount;

    //         $this->assertBalanced($totalDebit, $totalCredit);

    //         $journal->update([
    //             'total_debit' => $totalDebit,
    //             'total_credit' => $totalCredit,
    //             'status' => 'posted',
    //             'posted_at' => now(),
    //             'posted_by' => $actorId,
    //         ]);

    //         // Update reimbursement status to approved
    //         $reimbursement->update([
    //             'approval_status' => '2',
    //             'status' => '2',
    //             'approved_by' => $actorId,
    //         ]);

    //         return $journal;
    //     });
    // }

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
     * Settle payable accounts (Refund, Reimbursement)
     * Debit: Payable (Liability)
     * Credit: Cash/Bank (Asset)
     */
    /**
     * Settle one or more AP invoices (RFD-originated) for a single vendor with one payment.
     * Debit: Vendor AP (Liability)
     * Credit: Cash/Bank (Asset)
     */
    public function postDisbursement(
        array $apInvoiceIds,
        int $bankAccountId,
        int $paymentMethodId,
        float $amountToPay,
        string $referenceNumber,
        ?string $checkDate,
        int $actorId,
    ): ap_payment {
        return DB::transaction(function () use ($apInvoiceIds, $bankAccountId, $paymentMethodId, $amountToPay, $referenceNumber, $checkDate, $actorId) {
            $invoices = ap_invoice::whereIn('id', $apInvoiceIds)
                ->lockForUpdate()
                ->orderBy('due_date')
                ->orderBy('id')
                ->get();

            if ($invoices->isEmpty()) {
                throw new RuntimeException('No invoices selected for disbursement.');
            }
            if ($invoices->pluck('vendor_id')->unique()->count() > 1) {
                throw new RuntimeException('A single payment can only settle invoices for one vendor.');
            }
            if ($invoices->contains(fn($i) => in_array($i->status, ['paid', 'cancelled']))) {
                throw new RuntimeException('One or more selected invoices are not open for payment.');
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

            $totalDue = round((float) $invoices->sum('amount_due'), 2);
            $amountToPay = round($amountToPay, 2);

            if ($amountToPay <= 0) {
                throw new RuntimeException('Amount to pay must be greater than zero.');
            }
            if ($amountToPay > $totalDue) {
                throw new RuntimeException("Amount to pay ({$amountToPay}) cannot exceed the total amount due ({$totalDue}).");
            }
            if (!$paymentMethod->allow_partial_payment && $amountToPay < $totalDue) {
                throw new RuntimeException(
                    "Payment method '{$paymentMethod->name}' does not allow partial payment. " .
                    "Full amount due of {$totalDue} is required."
                );
            }

            $bankAccount = bank_account::findOrFail($bankAccountId);
            if (!$bankAccount->chart_of_account_id) {
                throw new RuntimeException('Selected bank/cash account has no linked GL account.');
            }

            $vendorId = $invoices->first()->vendor_id;
            $vendor = vendor::findOrFail($vendorId);
            $apAccountId = $this->resolveVendorApAccount($vendor);

            $journal = gl_journal::create([
                'organization_id' => $bankAccount->organization_id,
                'journal_no' => $this->nextNumber('GJ', gl_journal::class),
                'journal_date' => now()->toDateString(),
                'source_module' => 'rfd',
                'journal_type' => 'disbursement',
                'reference_type' => ap_payment::class,
                'reference_id' => 0,
                'description' => 'Vendor disbursement — ' . $referenceNumber,
                'status' => 'draft',
                'created_by' => $actorId,
            ]);

            gl_journal_line::create([
                'gl_journal_id' => $journal->id,
                'line_no' => 1,
                'gl_account_id' => $apAccountId,
                'debit' => $amountToPay,
                'credit' => 0,
                'description' => 'AP settlement — ' . $vendor->name,
                'subledger_type' => vendor::class,
                'subledger_id' => $vendorId,
                'created_by' => $actorId,
            ]);

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

            $payment = ap_payment::create([
                'organization_id' => $bankAccount->organization_id,
                'payment_no' => $this->nextNumber('PV', ap_payment::class),
                'vendor_id' => $vendorId,
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
                'approval_status' => '2',
                'posted_at' => now(),
                'posted_by' => $actorId,
            ]);

            $remaining = $amountToPay;
            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }
                $apply = min($remaining, (float) $invoice->amount_due);
                if ($apply <= 0) {
                    continue;
                }

                ap_payment_application::create([
                    'ap_payment_id' => $payment->id,
                    'ap_invoice_id' => $invoice->id,
                    'amount_applied' => $apply,
                ]);
                $invoice->applyPayment($apply);
                $remaining = round($remaining - $apply, 2);

                if ($invoice->source_type === rfd_header::class) {
                    $this->markRfdDisbursed((int) $invoice->source_id, $actorId);
                }
            }

            return $payment;
        });
    }
    // public function postDisbursement(
    //     array $payableIds,
    //     string $payableType, // 'refund', 'reimbursement'
    //     int $bankAccountId,
    //     int $paymentMethodId,
    //     float $amountToPay,
    //     string $referenceNumber,
    //     ?string $checkDate,
    //     int $actorId,
    // ): ap_payment {
    //     return DB::transaction(function () use ($payableIds, $payableType, $bankAccountId, $paymentMethodId, $amountToPay, $referenceNumber, $checkDate, $actorId) {

    //         // Get the payable records based on type
    //         $payables = $this->getPayableRecords($payableIds, $payableType);

    //         if ($payables->isEmpty()) {
    //             throw new RuntimeException('No payables selected for disbursement.');
    //         }

    //         $paymentMethod = payment_method::findOrFail($paymentMethodId);

    //         if ($paymentMethod->requires_bank && !$bankAccountId) {
    //             throw new RuntimeException("Payment method '{$paymentMethod->name}' requires a bank/cash account.");
    //         }
    //         if ($paymentMethod->requires_reference_no && !trim((string) $referenceNumber)) {
    //             throw new RuntimeException("Payment method '{$paymentMethod->name}' requires a reference number.");
    //         }
    //         if ($paymentMethod->requires_check && !$checkDate) {
    //             throw new RuntimeException("Payment method '{$paymentMethod->name}' requires a check date.");
    //         }

    //         $bankAccount = bank_account::findOrFail($bankAccountId);
    //         if (!$bankAccount->chart_of_account_id) {
    //             throw new RuntimeException('Selected bank/cash account has no linked GL account.');
    //         }

    //         // Get the payable account ID for debit
    //         $payableAccountId = $this->getPayableAccountId($payableType, $bankAccount->organization_id);

    //         $journal = gl_journal::create([
    //             'organization_id' => $bankAccount->organization_id,
    //             'journal_no' => $this->nextNumber('GJ', gl_journal::class),
    //             'journal_date' => now()->toDateString(),
    //             'source_module' => 'disbursement',
    //             'journal_type' => 'payable_disbursement',
    //             'reference_type' => CashAdvanceRefund::class,
    //             'reference_id' => 0,
    //             'description' => 'Payable disbursement — ' . $referenceNumber,
    //             'status' => 'draft',
    //             'created_by' => $actorId,
    //         ]);

    //         // Debit: Payable account (reducing the liability)
    //         gl_journal_line::create([
    //             'gl_journal_id' => $journal->id,
    //             'line_no' => 1,
    //             'gl_account_id' => $payableAccountId,
    //             'debit' => $amountToPay,
    //             'credit' => 0,
    //             'description' => 'Payment of ' . $payableType . ' payable',
    //             'created_by' => $actorId,
    //         ]);

    //         // Credit: Cash/Bank account
    //         gl_journal_line::create([
    //             'gl_journal_id' => $journal->id,
    //             'line_no' => 2,
    //             'gl_account_id' => $bankAccount->chart_of_account_id,
    //             'debit' => 0,
    //             'credit' => $amountToPay,
    //             'description' => 'Disbursement — ' . $referenceNumber,
    //             'created_by' => $actorId,
    //         ]);

    //         $this->assertBalanced($amountToPay, $amountToPay);

    //         // Create payment record
    //         $payment = ap_payment::create([
    //             'organization_id' => $bankAccount->organization_id,
    //             'payment_no' => $this->nextNumber('PV', ap_payment::class),
    //             'vendor_id' => null, // For employee payables, vendor is null
    //             'payment_date' => now()->toDateString(),
    //             'bank_account_id' => $bankAccount->id,
    //             'payment_method_id' => $paymentMethod->id,
    //             'reference_number' => $referenceNumber,
    //             'check_date' => $checkDate,
    //             'disbursement_method' => $paymentMethod->name,
    //             'currency_id' => $bankAccount->currency_id,
    //             'exchange_rate' => 1,
    //             'total_amount' => $amountToPay,
    //             'status' => 'posted',
    //             'gl_journal_id' => $journal->id,
    //             'created_by' => $actorId,
    //             'remarks' => 'Employee payable disbursement - ' . $referenceNumber,
    //         ]);

    //         $journal->update([
    //             'reference_id' => $payment->id,
    //             'total_debit' => $amountToPay,
    //             'total_credit' => $amountToPay,
    //             'status' => 'posted',
    //             'approval_status' => '2',
    //             'posted_at' => now(),
    //             'posted_by' => $actorId,
    //         ]);

    //         // Mark payables as paid
    //         $this->markPayablesAsPaid($payables, $actorId);

    //         return $payment;
    //     });
    // }

    /**
     * Get payable records based on type
     */
    protected function getPayableRecords(array $payableIds, string $payableType)
    {
        return match ($payableType) {
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
                'approval_status' => '2',
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