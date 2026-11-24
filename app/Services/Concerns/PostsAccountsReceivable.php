<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Models\chart_of_account;
use App\Models\collection;
use App\Models\collection_application;
use App\Models\customer;
use App\Models\gl_journal;
use App\Models\gl_journal_line;
use App\Models\invoice_series;
use App\Models\sales_invoice;
use App\Models\sales_invoice_line;
use App\Models\ar_setting;
use App\Models\credit_memo;
use App\Models\credit_memo_line;
use App\Models\credit_memo_line_tax;
use App\Models\debit_memo;
use App\Models\debit_memo_line;
use App\Models\debit_memo_line_tax;
use App\Models\debit_memo_application;
use Illuminate\Database\Eloquent\Model;

/**
 * AR posting for AccountingService.
 *
 * Install: in app/Services/AccountingService.php add
 *     use App\Services\PostsAccountsReceivable;            (top of file)
 *     use PostsAccountsReceivable;                         (first line inside the class)
 *
 * It reuses AccountingService::nextNumber() and ::assertBalanced(), and follows the same
 * pattern as postRfdApproval / postDisbursement (draft journal -> lines -> mark posted).
 *
 *   Invoice     Dr AR (customer)                  Cr Revenue (per line)   Cr Output VAT (tax_masters.gl_account_id)
 *   Collection  Dr Bank (amount received)         Cr AR (applied + CWT)
 *               Dr CWT Receivable (cwt_amount)    Cr Advance/Deposit (unapplied cash)
 */
trait PostsAccountsReceivable
{
    // ==================== SALES INVOICE ====================

    /** Called from ApprovalWorkflowService::postAccountingForModule() when the final step is approved. */
    public function postSalesInvoiceApproval(sales_invoice $invoice, int $actorId): gl_journal
    {
        return DB::transaction(function () use ($invoice, $actorId) {
            $invoice = sales_invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->gl_journal_id) {
                return $invoice->journal; // idempotent
            }

            $invoice->load(['customer', 'lines.taxes.tax.taxFormula']);

            if ($invoice->lines->isEmpty()) {
                throw new RuntimeException('Cannot post a sales invoice with no lines.');
            }

            $arAccountId = $this->resolveCustomerArAccount($invoice->customer);

            // ---------- Discount handling ----------
            // Toggle: on = gross method (Dr Sales Discount / Cr Revenue at gross),
            //         off = net method (no separate line; revenue net of discount).
            $arSettings = ar_setting::where('organization_id', $invoice->organization_id)->first();
            $recordDiscountInGl = (bool) ($arSettings->record_sales_discount_in_gl ?? false);

            $discountAccountId = null;
            $totalDiscount = round((float) $invoice->lines->sum(fn($l) => (float) $l->discount_amount), 2);

            if ($recordDiscountInGl && $totalDiscount != 0.0) {
                $discountAccountId = $invoice->customer->default_sales_discount_account_id
                    ?: ($arSettings->default_sales_discount_account_id ?? null);

                if (!$discountAccountId) {
                    throw new RuntimeException(
                        "Cannot post sales discount: customer '{$invoice->customer->name}' has no "
                        . 'discount account and no AR Settings default. Set '
                        . 'customers.default_sales_discount_account_id or ar_settings.default_sales_discount_account_id.'
                    );
                }
            }

            // ---------- Revenue + VAT credits ----------
            $isInclusive = (int) $invoice->tax_option === 1;

            $credits = [];
            foreach ($invoice->lines as $line) {
                if (!$line->gl_account_id) {
                    throw new RuntimeException("Line {$line->line_no}: no revenue account. Edit the invoice line first.");
                }

                $lineTax = round((float) $line->tax_amount, 2);
                $lineDiscount = round((float) $line->discount_amount, 2);

                if ($recordDiscountInGl) {
                    // Gross revenue: reconstruct the pre-discount taxable
                    $lineGrossTaxable = round((float) $line->taxable_amount + $lineDiscount, 2);
                    $lineRevenue = $isInclusive
                        ? round($lineGrossTaxable - $lineTax, 2)
                        : $lineGrossTaxable;
                } else {
                    // Net revenue: taxable is already net of discount
                    $lineRevenue = $isInclusive
                        ? round((float) $line->taxable_amount - $lineTax, 2)
                        : round((float) $line->taxable_amount, 2);
                }

                if ($lineRevenue != 0.0) {
                    $credits[] = [
                        'gl_account_id' => $line->gl_account_id,
                        'debit' => $lineRevenue < 0 ? abs($lineRevenue) : 0,
                        'credit' => $lineRevenue > 0 ? $lineRevenue : 0,
                        'description' => mb_substr((string) $line->description, 0, 250),
                        'subledger_type' => sales_invoice_line::class,
                        'subledger_id' => $line->id,
                    ];
                }

                foreach ($line->taxes as $lineTax) {
                    $tax = $lineTax->tax;
                    if ($tax->taxFormula?->operation === 'Deduct') {
                        throw new RuntimeException("Tax {$tax->code} is a withholding tax. Withholding is recorded on the Collection, not on the invoice.");
                    }
                    $amt = round((float) $lineTax->tax_amount, 2);
                    if ($amt == 0.0) {
                        continue;
                    }
                    if (!$tax->gl_account_id) {
                        throw new RuntimeException("Tax {$tax->code} has no GL account. Set it in Tax Management > Tax Master.");
                    }
                    $credits[] = [
                        'gl_account_id' => $tax->gl_account_id,
                        'debit' => 0,
                        'credit' => $amt,
                        'description' => 'Tax: ' . $tax->code,
                        'subledger_type' => sales_invoice_line::class,
                        'subledger_id' => $line->id,
                    ];
                }
            }

            // ---------- Discount debit (only when gross method is on) ----------
            if ($recordDiscountInGl && $totalDiscount != 0.0) {
                $credits[] = [
                    'gl_account_id' => (int) $discountAccountId,
                    'debit' => $totalDiscount,
                    'credit' => 0,
                    'description' => 'Sales discount — ' . $invoice->displayNo(),
                    'subledger_type' => null,
                    'subledger_id' => null,
                ];
            }

            $receivable = round(
                array_sum(array_column($credits, 'credit')) - array_sum(array_column($credits, 'debit')),
                2
            );

            if (abs($receivable - round((float) $invoice->total_amount, 2)) > 0.01) {
                throw new RuntimeException('Invoice total does not match its lines. Recalculate the invoice before posting.');
            }

            // ---------- Numbering (unchanged) ----------
            // if (!$invoice->invoice_no) {
            //     $issued = invoice_series::issue($invoice->organization_id, 'sales_invoice', $invoice->invoice_date);
            //     if (!$issued) {
            //         throw new RuntimeException('No active Sales Invoice series configured (Accounts Receivable > Invoice Series).');
            //     }
            //     $invoice->invoice_series_id = $issued['series_id'];
            //     $invoice->invoice_no = $issued['number'];
            // }

            $lines = array_merge([
                [
                    'gl_account_id' => $arAccountId,
                    'debit' => $receivable,
                    'credit' => 0,
                    'description' => 'AR — ' . $invoice->customer->name . ' — ' . $invoice->invoice_no,
                    'subledger_type' => customer::class,
                    'subledger_id' => $invoice->customer_id,
                ]
            ], $credits);

            $journal = $this->createArJournal([
                'organization_id' => $invoice->organization_id,
                'journal_date' => $invoice->invoice_date->toDateString(),
                'source_module' => 'ar_invoice',
                'journal_type' => 'sales_invoice',
                'reference_type' => sales_invoice::class,
                'reference_id' => $invoice->id,
                'description' => 'Sales Invoice ' . $invoice->invoice_no . ' — ' . $invoice->customer->name,
            ], $lines, $actorId);

            $invoice->forceFill([
                'approval_status' => '2',
                'payment_status' => 'unpaid',
                'amount_due' => $receivable,
                'bill_to_name' => $invoice->bill_to_name ?: ($invoice->customer->legal_name ?: $invoice->customer->name),
                'bill_to_tin' => $invoice->bill_to_tin ?: $invoice->customer->tax_id,
                'gl_journal_id' => $journal->id,
                'posted_at' => now(),
                'posted_by' => $actorId,
            ])->save();

            return $journal;
        });
    }

    // ==================== COLLECTION ====================

    /**
     * Post a collection and apply it to invoices in one transaction.
     * Called from ApprovalWorkflowService when the FINAL approval step is approved. The
     * collection_applications rows already exist (saved on create / edited by approvers),
     * so they are updated here, never re-created.
     *
     * @param array<int, array{sales_invoice_id:int, amount_applied:float, cwt_applied?:float}> $applications
     */
    public function postCollection(collection $collection, array $applications, int $actorId): gl_journal
    {
        return DB::transaction(function () use ($collection, $applications, $actorId) {
            $collection = collection::whereKey($collection->id)->lockForUpdate()->firstOrFail();

            if ($collection->gl_journal_id) {
                return $collection->journal; // idempotent
            }

            $collection->load(['customer', 'bankAccount', 'cwtTax']);

            $bankCoa = $collection->bankAccount?->chart_of_account_id;
            if (!$bankCoa) {
                throw new RuntimeException('Selected bank/cash account has no linked GL account.');
            }
            $arAccountId = $this->resolveCustomerArAccount($collection->customer);

            $received = round((float) $collection->amount_received, 2);
            $cwt = round((float) $collection->cwt_amount, 2);
            $appliedTotal = 0.0;
            $cwtApplied = 0.0;
            $toApply = [];

            // Lock invoices in a stable order so two collections touching the same invoices
            // cannot deadlock each other.
            usort($applications, fn($a, $b) => (int) $a['sales_invoice_id'] <=> (int) $b['sales_invoice_id']);

            foreach ($applications as $app) {
                $invoice = sales_invoice::whereKey($app['sales_invoice_id'])->lockForUpdate()->firstOrFail();

                if ((int) $invoice->customer_id !== (int) $collection->customer_id) {
                    throw new RuntimeException("Invoice {$invoice->invoice_no} belongs to a different customer.");
                }
                if (!$invoice->isOpen()) {
                    throw new RuntimeException("Invoice {$invoice->invoice_no} is not open for collection.");
                }

                $amt = round((float) $app['amount_applied'], 2);
                $w = round((float) ($app['cwt_applied'] ?? 0), 2);

                if (($amt + $w) - (float) $invoice->amount_due > 0.01) {
                    throw new RuntimeException("Amount applied to {$invoice->invoice_no} exceeds its balance of " . number_format($invoice->amount_due, 2) . '.');
                }

                $appliedTotal += $amt;
                $cwtApplied += $w;
                $toApply[] = [$invoice, $amt, $w];
            }

            $appliedTotal = round($appliedTotal, 2);
            $cwtApplied = round($cwtApplied, 2);
            $unapplied = round($received - $appliedTotal, 2);

            if ($unapplied < -0.01) {
                throw new RuntimeException('Applied amount is more than the amount received.');
            }
            if (abs($cwtApplied - $cwt) > 0.01) {
                throw new RuntimeException('Tax withheld must be fully allocated to invoices (applied CWT does not equal the CWT amount).');
            }
            if ($cwt > 0 && !$collection->cwtTax?->gl_account_id) {
                throw new RuntimeException('Select a withholding tax whose GL account (Creditable Withholding Tax receivable) is configured.');
            }
            if ($unapplied > 0.01 && !$collection->advance_account_id) {
                throw new RuntimeException('Select the credit account (e.g. Customer Deposits) for the unapplied amount.');
            }

            $ref = $collection->reference_number ?: $collection->check_number ?: $collection->collection_no;
            $lines = [];
            if ($received > 0) {
                $lines[] = [
                    'gl_account_id' => $bankCoa,
                    'debit' => $received,
                    'credit' => 0,
                    'description' => 'Collection — ' . $ref,
                    'subledger_type' => null,
                    'subledger_id' => null
                ];
            }
            if ($cwt > 0) {
                $lines[] = [
                    'gl_account_id' => $collection->cwtTax->gl_account_id,
                    'debit' => $cwt,
                    'credit' => 0,
                    'description' => 'Creditable withholding tax (BIR 2307)',
                    'subledger_type' => null,
                    'subledger_id' => null
                ];
            }
            if (($appliedTotal + $cwtApplied) > 0) {
                $lines[] = [
                    'gl_account_id' => $arAccountId,
                    'debit' => 0,
                    'credit' => round($appliedTotal + $cwtApplied, 2),
                    'description' => 'AR settlement — ' . $collection->customer->name,
                    'subledger_type' => customer::class,
                    'subledger_id' => $collection->customer_id
                ];
            }
            if ($unapplied > 0.01) {
                $lines[] = [
                    'gl_account_id' => $collection->advance_account_id,
                    'debit' => 0,
                    'credit' => $unapplied,
                    'description' => 'Unapplied collection — ' . $collection->customer->name,
                    'subledger_type' => customer::class,
                    'subledger_id' => $collection->customer_id
                ];
            }

            $journal = $this->createArJournal([
                'organization_id' => $collection->organization_id,
                'journal_date' => $collection->collection_date->toDateString(),
                'source_module' => 'ar_collection',
                'journal_type' => 'collection',
                'reference_type' => collection::class,
                'reference_id' => $collection->id,
                'description' => 'Collection ' . $collection->collection_no . ' — ' . $collection->customer->name,
            ], $lines, $actorId);

            foreach ($toApply as [$invoice, $amt, $w]) {
                collection_application::updateOrCreate(
                    ['collection_id' => $collection->id, 'sales_invoice_id' => $invoice->id],
                    ['amount_applied' => $amt, 'cwt_applied' => $w]
                );
                $invoice->applySettlement($amt, $w);
            }

            $collection->forceFill([
                'amount_applied' => $appliedTotal,
                'amount_unapplied' => max(0, $unapplied),
                'cwt_amount' => $cwtApplied,
                'gl_journal_id' => $journal->id,
                'status' => 'posted',
                'posted_at' => now(),
            ])->save();

            return $journal;
        });
    }

    /**
     * Issue the collection (receipt) number from the default active `collection_receipt` series.
     * Same mechanism as InvoiceController::issueInvoiceNumber(). MUST be called inside the
     * caller's DB::transaction so that if anything later fails (no workflow, validation) the
     * increment rolls back and the BIR sequence keeps no gap. Idempotent: a returned
     * collection that is resubmitted keeps the number it already has.
     */
    public function issueCollectionNo(collection $collection): string
    {
        if ($collection->collection_no) {
            return $collection->collection_no;
        }

        $seriesId = invoice_series::where('organization_id', $collection->organization_id)
            ->where('document_type', 'collection_receipt')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        if (!$seriesId) {
            throw new RuntimeException(
                'Cannot issue a collection number: no active Collection Receipt series is configured (Accounts Receivable > Invoice Series).'
            );
        }

        $series = invoice_series::whereKey($seriesId)->lockForUpdate()->firstOrFail();
        $series->assertIssuable(); // is_active, valid_until, series_end

        $number = $series->nextLabel();
        $series->forceFill(['next_number' => (int) $series->next_number + 1])->save();

        $collection->forceFill(['collection_no' => $number])->save();

        return $number;
    }

    // ==================== CREDIT / DEBIT MEMOS ====================
    //
    //  Credit memo (reduces what the customer owes - returns, allowances, billing corrections)
    //      Dr Sales Returns & Allowances / Revenue (per line, net of VAT)
    //      Dr Output VAT                           (reverses the VAT originally declared)
    //          Cr Accounts Receivable (customer)
    //
    //  Debit memo (increases what the customer owes - additional charges on an issued invoice)
    //      Dr Accounts Receivable (customer)
    //          Cr Revenue (per line, net of VAT)
    //          Cr Output VAT
    //
    //  Both reference the original invoice (BIR), take their number from invoice_series on submit,
    //  and post only when the FINAL approval step is approved.

    /** Called from ApprovalWorkflowService::postAccountingForModule() on final approval. */
    public function postCreditMemoApproval(credit_memo $memo, int $actorId): gl_journal
    {
        return $this->postMemo($memo, 'credit', $actorId);
    }

    /** Called from ApprovalWorkflowService::postAccountingForModule() on final approval. */
    public function postDebitMemoApproval(debit_memo $memo, int $actorId): gl_journal
    {
        return $this->postMemo($memo, 'debit', $actorId);
    }

    protected function postMemo(Model $memo, string $type, int $actorId): gl_journal
    {
        $isCredit = $type === 'credit';
        $class = $isCredit ? credit_memo::class : debit_memo::class;
        $noCol = $isCredit ? 'credit_memo_no' : 'debit_memo_no';
        $dateCol = $isCredit ? 'credit_date' : 'debit_date';
        $label = $isCredit ? 'Credit Memo' : 'Debit Memo';

        return DB::transaction(function () use ($memo, $class, $isCredit, $noCol, $dateCol, $label, $type, $actorId) {
            $memo = $class::whereKey($memo->id)->lockForUpdate()->firstOrFail();

            if ($memo->gl_journal_id) {
                return $memo->journal; // idempotent
            }

            $memo->load(['customer', 'lines.taxes.tax.taxFormula', 'applications']);

            if ($memo->lines->isEmpty()) {
                throw new RuntimeException("Cannot post a {$label} with no lines.");
            }

            $no = $memo->{$noCol} ?: $memo->displayNo();
            $arAccountId = $this->resolveCustomerArAccount($memo->customer);
            $isInclusive = (int) $memo->tax_option === 1;

            // Entries opposite the AR line. A negative amount goes on the other side.
            $entries = [];
            $push = function (int $accountId, float $amount, string $desc, string $slType, int $slId) use (&$entries, $isCredit) {
                if ($amount == 0.0) {
                    return;
                }
                $onNormalSide = $amount > 0;
                $abs = abs($amount);
                // credit memo: normal side = debit ; debit memo: normal side = credit
                $debit = ($isCredit === $onNormalSide) ? $abs : 0;
                $credit = ($isCredit === $onNormalSide) ? 0 : $abs;
                $entries[] = [
                    'gl_account_id' => $accountId,
                    'debit' => $debit,
                    'credit' => $credit,
                    'description' => mb_substr($desc, 0, 250),
                    'subledger_type' => $slType,
                    'subledger_id' => $slId,
                ];
            };

            $lineClass = $isCredit ? credit_memo_line::class : debit_memo_line::class;

            foreach ($memo->lines as $line) {
                if (!$line->gl_account_id) {
                    throw new RuntimeException("Line {$line->line_no}: no " . ($isCredit ? 'contra-revenue' : 'revenue') . ' account.');
                }

                $taxable = round((float) $line->taxable_amount, 2);
                $lineTax = round((float) $line->tax_amount, 2);
                $lineRevenue = $isInclusive ? round($taxable - $lineTax, 2) : $taxable;

                $push((int) $line->gl_account_id, $lineRevenue, (string) $line->description, $lineClass, (int) $line->id);

                foreach ($line->taxes as $lineTaxRow) {
                    $tax = $lineTaxRow->tax;
                    if (!$tax) {
                        continue;
                    }
                    if ($tax->taxFormula?->operation === 'Deduct') {
                        throw new RuntimeException("Tax {$tax->code} is a withholding tax. Withholding is recorded on the Collection, not on a memo.");
                    }
                    $amt = round((float) $lineTaxRow->tax_amount, 2);
                    if ($amt == 0.0) {
                        continue;
                    }
                    if (!$tax->gl_account_id) {
                        throw new RuntimeException("Tax {$tax->code} has no GL account. Set it in Tax Management > Tax Master.");
                    }
                    $push((int) $tax->gl_account_id, $amt, ($isCredit ? 'Output VAT reversal — ' : 'Output VAT — ') . $tax->code, $lineClass, (int) $line->id);
                }
            }

            // Net of the entries = the receivable effect.
            $net = round(
                $isCredit
                ? array_sum(array_column($entries, 'debit')) - array_sum(array_column($entries, 'credit'))
                : array_sum(array_column($entries, 'credit')) - array_sum(array_column($entries, 'debit')),
                2
            );

            if ($net <= 0) {
                throw new RuntimeException("{$label} has no positive amount to post.");
            }
            if (abs($net - round((float) $memo->total_amount, 2)) > 0.01) {
                throw new RuntimeException("{$label} total does not match its lines. Recalculate the memo before posting.");
            }

            // Original invoice, credit cap and application limits - re-checked under lock,
            // because the invoice may have changed while the memo waited for approval.
            // Null only for a standalone credit memo (e.g. a trade rebate not tied to an invoice).
            $invoice = $this->assertMemoAgainstInvoice($memo, $type);

            $arLine = [
                'gl_account_id' => $arAccountId,
                'debit' => $isCredit ? 0 : $net,
                'credit' => $isCredit ? $net : 0,
                'description' => "AR — {$label} {$no} — " . $memo->customer->name,
                'subledger_type' => customer::class,
                'subledger_id' => $memo->customer_id,
            ];
            $lines = $isCredit ? array_merge($entries, [$arLine]) : array_merge([$arLine], $entries);

            $journal = $this->createArJournal([
                'organization_id' => $memo->organization_id,
                'journal_date' => $memo->{$dateCol}->toDateString(),
                'source_module' => $isCredit ? 'ar_credit_memo' : 'ar_debit_memo',
                'journal_type' => $isCredit ? 'credit_memo' : 'debit_memo',
                'reference_type' => $class,
                'reference_id' => $memo->id,
                'description' => "{$label} {$no} — " . $memo->customer->name . ($invoice ? ' (inv ' . $invoice->invoice_no . ')' : ''),
            ], $lines, $actorId);

            // Effect on the invoice subledger
            if ($isCredit) {
                $applied = 0.0;
                foreach ($invoice ? $memo->applications : [] as $app) {
                    $amt = round((float) $app->amount_applied, 2);
                    if ($amt > 0) {
                        $invoice->applyCreditMemo($amt);
                        $applied += $amt;
                    }
                }
                $applied = round($applied, 2);
                $memo->forceFill([
                    'amount_applied' => $applied,
                    'amount_unapplied' => round($net - $applied, 2),
                ]);
            } else {
                $invoice->applyDebitMemo($net);
                debit_memo_application::updateOrCreate(
                    ['debit_memo_id' => $memo->id, 'sales_invoice_id' => $invoice->id],
                    ['amount_applied' => $net]
                );
                $memo->forceFill(['amount_applied' => $net, 'amount_unapplied' => 0]);
            }

            $memo->forceFill([
                'status' => '2', //approved and posted
                'approval_status' => '2',
                'gl_journal_id' => $journal->id,
                'posted_at' => now(),
                'posted_by' => $actorId,
            ])->save();

            return $journal;
        });
    }

    /**
     * Business rules shared by submit, approver edits and posting. Locks the original invoice,
     * so call it inside a DB::transaction. Returns the locked invoice, or null for a standalone credit memo.
     *
     *  - the memo references an approved, non-voided invoice of the same customer and organization
     *  - credit memo: total credited against the invoice (pending + approved memos) cannot exceed
     *    what was billed (invoice total + debit memos)
     *  - credit memo: the amount applied cannot exceed the memo total or the invoice's open balance
     */
    public function assertMemoAgainstInvoice(Model $memo, string $type): ?sales_invoice
    {
        $isCredit = $type === 'credit';
        $label = $isCredit ? 'Credit memo' : 'Debit memo';

        if (!$memo->original_invoice_id) {
            if (!$isCredit) {
                throw new RuntimeException('A debit memo must reference the invoice it adds to.');
            }
            if ($memo->applications->isNotEmpty()) {
                throw new RuntimeException('A standalone credit memo cannot be applied to an invoice.');
            }

            return null; // standalone credit: stays as unapplied credit to the customer
        }

        $invoice = sales_invoice::whereKey($memo->original_invoice_id)->lockForUpdate()->first();
        if (!$invoice) {
            throw new RuntimeException('The original invoice no longer exists.');
        }
        if (
            (int) $invoice->organization_id !== (int) $memo->organization_id
            || (int) $invoice->customer_id !== (int) $memo->customer_id
        ) {
            throw new RuntimeException("The original invoice {$invoice->invoice_no} belongs to a different customer.");
        }
        if ($invoice->approval_status !== '2' || $invoice->voided_at) {
            throw new RuntimeException("Invoice {$invoice->invoice_no} is not an approved, active invoice.");
        }

        if (!$isCredit) {
            return $invoice;
        }

        $total = round((float) $memo->total_amount, 2);

        $others = (float) credit_memo::where('original_invoice_id', $invoice->id)
            ->where('id', '!=', $memo->id)
            ->whereIn('approval_status', ['1', '2'])
            ->whereNull('voided_at')
            ->sum('total_amount');
        $billed = round((float) $invoice->total_amount + (float) ($invoice->debit_applied ?? 0), 2);

        if (round($others + $total, 2) - $billed > 0.01) {
            throw new RuntimeException(sprintf(
                'Credit memos against invoice %s would total %s, more than the %s billed.',
                $invoice->invoice_no,
                number_format($others + $total, 2),
                number_format($billed, 2)
            ));
        }

        $applied = 0.0;
        foreach ($memo->applications as $app) {
            if ((int) $app->sales_invoice_id !== (int) $invoice->id) {
                throw new RuntimeException('A credit memo can only be applied to its original invoice.');
            }
            $applied += (float) $app->amount_applied;
        }
        $applied = round($applied, 2);

        if ($applied - $total > 0.01) {
            throw new RuntimeException('The amount applied (' . number_format($applied, 2) . ') exceeds the memo total (' . number_format($total, 2) . ').');
        }
        if ($applied > 0.01 && $applied - (float) $invoice->amount_due > 0.01) {
            throw new RuntimeException(sprintf(
                'The amount applied (%s) exceeds the open balance of invoice %s (%s).',
                number_format($applied, 2),
                $invoice->invoice_no,
                number_format((float) $invoice->amount_due, 2)
            ));
        }

        return $invoice;
    }

    /**
     * Void a posted credit/debit memo: reverse the invoice effect and post a reversing journal.
     * The numbered document stays on record (BIR) with status 'voided'.
     */
    public function voidMemoPosting(Model $memo, string $type, int $actorId, string $reason): void
    {
        $isCredit = $type === 'credit';
        $class = $isCredit ? credit_memo::class : debit_memo::class;
        $noCol = $isCredit ? 'credit_memo_no' : 'debit_memo_no';
        $label = $isCredit ? 'Credit Memo' : 'Debit Memo';

        DB::transaction(function () use ($memo, $class, $isCredit, $noCol, $label, $actorId, $reason) {
            $memo = $class::whereKey($memo->id)->lockForUpdate()->firstOrFail();

            if ($memo->voided_at) {
                throw new RuntimeException("This {$label} is already voided.");
            }
            if (!$memo->gl_journal_id) {
                throw new RuntimeException("This {$label} has not been posted.");
            }

            $memo->load(['applications', 'journal.lines']);
            $invoice = sales_invoice::whereKey($memo->original_invoice_id)->lockForUpdate()->first();

            if ($invoice) {
                if ($isCredit) {
                    foreach ($memo->applications as $app) {
                        $invoice->reverseCreditMemo((float) $app->amount_applied);
                    }
                } else {
                    $amount = round((float) $memo->total_amount, 2);
                    if ((float) $invoice->amount_due + 0.01 < $amount) {
                        throw new RuntimeException(
                            "Invoice {$invoice->invoice_no} has already been collected against this debit memo. Reverse the collection first."
                        );
                    }
                    $invoice->reverseDebitMemo($amount);
                }
            }

            $no = $memo->{$noCol} ?: $memo->displayNo();
            $lines = $memo->journal->lines->map(fn($l) => [
                'gl_account_id' => $l->gl_account_id,
                'debit' => (float) $l->credit,
                'credit' => (float) $l->debit,
                'description' => mb_substr('Reversal — ' . $l->description, 0, 250),
                'subledger_type' => $l->subledger_type,
                'subledger_id' => $l->subledger_id,
            ])->all();

            $this->createArJournal([
                'organization_id' => $memo->organization_id,
                'journal_date' => today()->toDateString(),
                'source_module' => $isCredit ? 'ar_credit_memo' : 'ar_debit_memo',
                'journal_type' => 'reversal',
                'reference_type' => $class,
                'reference_id' => $memo->id,
                'description' => "Void {$label} {$no}: {$reason}",
            ], $lines, $actorId);

            $memo->forceFill([
                'status' => '3', //voided
                'voided_at' => now(),
                'voided_by' => $actorId,
                'void_reason' => $reason,
            ])->save();
        });
    }

    /**
     * Issue a document number from the document's own series (BIR sequence).
     * MUST run inside the caller's DB::transaction so a later failure rolls the increment back
     * and the sequence keeps no gap. Idempotent for documents that already have a number.
     */
    public function issueMemoNo(Model $memo, string $seriesType, string $noCol, string $seriesCol): string
    {
        if ($memo->{$noCol}) {
            return $memo->{$noCol};
        }

        $seriesId = $memo->{$seriesCol}
            ?: invoice_series::where('organization_id', $memo->organization_id)
                ->where('document_type', $seriesType)
                ->where('is_active', true)
                ->orderByDesc('is_default')->orderBy('id')
                ->value('id');

        if (!$seriesId) {
            throw new RuntimeException(
                "Cannot issue a number: no active '{$seriesType}' series is configured (Accounts Receivable > Invoice Series)."
            );
        }

        $series = invoice_series::whereKey($seriesId)->lockForUpdate()->firstOrFail();
        if ((int) $series->organization_id !== (int) $memo->organization_id || $series->document_type !== $seriesType) {
            throw new RuntimeException("Series {$series->code} is not a {$seriesType} series for this organization.");
        }
        $series->assertIssuable(); // is_active, valid_until, series_end

        $number = $series->nextLabel();
        $series->forceFill(['next_number' => (int) $series->next_number + 1])->save();

        $memo->forceFill([$noCol => $number, $seriesCol => $series->id])->save();

        return $number;
    }

    // ==================== HELPERS ====================

    /** Mirrors resolveVendorApAccount(): explicit default first, else the single posting COA of the main account. */
    protected function resolveCustomerArAccount(customer $customer): int
    {
        if ($customer->default_ar_chart_of_account_id) {
            return $customer->default_ar_chart_of_account_id;
        }

        // Org-wide default from AR Settings
        $settingsAccount = ar_setting::where('organization_id', $customer->organization_id)
            ->value('default_ar_account_id');
        if ($settingsAccount) {
            return (int) $settingsAccount;
        }

        // Legacy: single posting COA under the customer's main account
        if ($customer->ar_account_id) {
            $candidates = chart_of_account::where('organization_id', $customer->organization_id)
                ->where('main_account_id', $customer->ar_account_id)
                ->where('is_posting', true)
                ->where('status', true)
                ->get();

            if ($candidates->count() === 1) {
                return $candidates->first()->id;
            }
        }

        throw new RuntimeException(
            "Customer '{$customer->name}' has no posting AR account. "
            . 'Set customers.default_ar_chart_of_account_id or configure default_ar_account_id in AR Settings.'
        );
    }

    /**
     * Same shape as the journals in postRfdApproval/postDisbursement: create as draft, add lines,
     * assert balance, then mark posted.
     *
     * @param array<int, array<string,mixed>> $lines
     */
    protected function createArJournal(array $head, array $lines, int $actorId): gl_journal
    {
        $journal = gl_journal::create(array_merge($head, [
            'journal_no' => $this->nextNumber('GJ', gl_journal::class),
            'status' => 'draft',
            'created_by' => $actorId,
        ]));

        $totalDebit = 0;
        $totalCredit = 0;

        foreach (array_values($lines) as $i => $line) {
            gl_journal_line::create(array_merge($line, [
                'gl_journal_id' => $journal->id,
                'line_no' => $i + 1,
                'created_by' => $actorId,
            ]));
            $totalDebit += $line['debit'];
            $totalCredit += $line['credit'];
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
    }
}