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


class AccountingService
{
    /**
     * Called by ApprovalWorkflowService::approve() when the final step approves.
     * Creates one balanced GL journal for the whole RFD, and one ap_invoice per vendor.
     */
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

    /**
     * Settle one or more open AP invoices belonging to the same vendor with a single payment.
     * Enforces the selected payment method's allow_partial_payment flag, and — when a
     * partial amount is allowed and used — applies it across the selected invoices in
     * due-date order (oldest first) rather than requiring the user to split it manually.
     * Creates a balanced GL journal (Dr AP / Cr Bank) for the actual amount paid.
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
                'source_module' => 'ap_payment',
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
                'disbursement_method' => $paymentMethod->name, // snapshot
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

            // Apply the payment across the selected invoices oldest-due-first until exhausted.
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