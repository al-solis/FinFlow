<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use App\Models\customer;
use App\Models\term;
use App\Models\currency;
use App\Models\invoice_series;
use App\Models\sales_invoice_line;
use App\Models\sales_invoice_line_tax;
use App\Models\gl_journal;
use App\Models\gl_journal_line;
use App\Models\credit_memo;
use App\Models\debit_memo;

class sales_invoice extends Model
{
    protected $table = 'sales_invoices';
    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'billing_period_from' => 'date',
        'billing_period_to' => 'date',
        'submitted_at' => 'datetime',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

    public function customer()
    {
        return $this->belongsTo(customer::class, 'customer_id');
    }

    public function term()
    {
        return $this->belongsTo(term::class, 'term_id');
    }

    public function currency()
    {
        return $this->belongsTo(currency::class, 'currency_id');
    }

    public function series()
    {
        return $this->belongsTo(invoice_series::class, 'invoice_series_id');
    }

    public function lines()
    {
        return $this->hasMany(sales_invoice_line::class, 'sales_invoice_id')->orderBy('line_no');
    }

    public function journal()
    {
        return $this->belongsTo(gl_journal::class, 'gl_journal_id');
    }

    public function source()
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }

    public function creditMemos()
    {
        return $this->hasMany(credit_memo::class, 'original_invoice_id');
    }

    public function debitMemos()
    {
        return $this->hasMany(debit_memo::class, 'original_invoice_id');
    }

    public function postedCreditMemos()
    {
        return $this->creditMemos()
            ->where('approval_status', '2')
            ->whereNull('voided_at');
    }

    public function postedDebitMemos()
    {
        return $this->debitMemos()
            ->where('approval_status', '2')
            ->whereNull('voided_at');
    }

    public function applications()
    {
        return $this->hasMany(collection_application::class, 'sales_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvalTransactions()
    {
        return $this->morphMany(approval_transaction::class, 'approvable');
    }

    public function latestApprovalTransaction()
    {
        return $this->morphOne(approval_transaction::class, 'approvable')->latestOfMany();
    }

    // ------------------------------------------------------------------
    // Display
    // ------------------------------------------------------------------

    /**
     * FIX: the list view already calls $inv->displayNo() — define it here so
     * the index page doesn't throw BadMethodCallException. Falls back to the
     * series label when the invoice_no is still null (drafts), and to a
     * placeholder for drafts with no series.
     */
    public function displayNo(): string
    {
        if ($this->invoice_no) {
            return $this->invoice_no;
        }
        if ($this->relationLoaded('series') && $this->series) {
            return 'Draft — ' . $this->series->code;
        }
        if ($this->invoice_series_id) {
            $series = invoice_series::find($this->invoice_series_id);
            if ($series) {
                return 'Draft — ' . $series->code;
            }
        }

        return 'Draft #' . $this->id;
    }

    // ------------------------------------------------------------------
    // Totals
    // ------------------------------------------------------------------

    /** Recompute header totals from lines. Call after lines/taxes are saved. */
    public function recalcTotals(): void
    {
        $lines = $this->lines()->get();

        $this->subtotal = round($lines->sum(fn($l) => (float) $l->quantity * (float) $l->unit_price), 2);
        $this->discount_amount = round($lines->sum('discount_amount'), 2);
        $this->vatable_sales = round($lines->where('vat_class', 'vatable')->sum('taxable_amount'), 2);
        $this->zero_rated_sales = round($lines->where('vat_class', 'zero_rated')->sum('taxable_amount'), 2);
        $this->vat_exempt_sales = round($lines->whereIn('vat_class', ['exempt', 'non_vat'])->sum('taxable_amount'), 2);
        $this->vat_amount = round($lines->sum('tax_amount'), 2);
        $this->total_amount = round($lines->sum('total_amount'), 2);
        $this->amount_due = round($this->total_amount + (float) $this->debit_applied - $this->amount_paid - $this->cwt_applied - $this->credit_applied, 2);
        $this->save();
    }

    // ------------------------------------------------------------------
    // Settlement (concurrency-safe)
    // ------------------------------------------------------------------

    /**
     * FIX: the previous version did `$this->amount_paid = round($this->amount_paid + $amount, 2)`
     * on a stale in-memory value, so two concurrent collections both read the
     * old amount_paid and one write won. Wrap in a DB-level UPDATE ... SET
     * amount_paid = amount_paid + ? so the read-modify-write is a single
     * atomic statement inside the caller's transaction.
     *
     * The caller must be inside DB::transaction().
     */
    public function applySettlement(float $amount, float $cwt = 0): void
    {
        DB::table('sales_invoices')->where('id', $this->id)->update([
            'amount_paid' => DB::raw('amount_paid + ' . (float) $amount),
            'cwt_applied' => DB::raw('cwt_applied + ' . (float) $cwt),
        ]);

        $this->refresh()->refreshBalance();
    }

    public function reverseSettlement(float $amount, float $cwt = 0): void
    {
        DB::table('sales_invoices')->where('id', $this->id)->update([
            'amount_paid' => DB::raw('amount_paid - ' . (float) $amount),
            'cwt_applied' => DB::raw('cwt_applied - ' . (float) $cwt),
        ]);

        $this->refresh()->refreshBalance();
    }

    // ------------------------------------------------------------------
    // Credit / debit memos (concurrency-safe, same pattern as applySettlement)
    // Caller must be inside DB::transaction().
    // ------------------------------------------------------------------

    /** A posted credit memo settles part of the balance (credit_applied goes up). */
    public function applyCreditMemo(float $amount): void
    {
        DB::table('sales_invoices')->where('id', $this->id)->update([
            'credit_applied' => DB::raw('credit_applied + ' . round($amount, 2)),
        ]);

        $this->refresh()->refreshBalance();
    }

    public function reverseCreditMemo(float $amount): void
    {
        DB::table('sales_invoices')->where('id', $this->id)->update([
            'credit_applied' => DB::raw('credit_applied - ' . round($amount, 2)),
        ]);

        $this->refresh()->refreshBalance();
    }

    /** A posted debit memo adds to what the customer owes on this invoice (debit_applied goes up). */
    public function applyDebitMemo(float $amount): void
    {
        DB::table('sales_invoices')->where('id', $this->id)->update([
            'debit_applied' => DB::raw('debit_applied + ' . round($amount, 2)),
        ]);

        $this->refresh()->refreshBalance();
    }

    public function reverseDebitMemo(float $amount): void
    {
        DB::table('sales_invoices')->where('id', $this->id)->update([
            'debit_applied' => DB::raw('debit_applied - ' . round($amount, 2)),
        ]);

        $this->refresh()->refreshBalance();
    }

    protected function refreshBalance(): void
    {
        // debit memos add to what is owed; credit memos and collections reduce it
        $amountDue = round($this->total_amount + (float) $this->debit_applied - $this->amount_paid - $this->cwt_applied - $this->credit_applied, 2);

        $status = match (true) {
            $amountDue <= 0.01 => 'paid',
            ($this->amount_paid + $this->cwt_applied + $this->credit_applied) > 0.01 => 'partially_paid',
            default => 'unpaid',
        };

        DB::table('sales_invoices')->where('id', $this->id)->update([
            'amount_due' => $amountDue,
            'payment_status' => $status,
        ]);

        $this->refresh();
    }

    // ------------------------------------------------------------------
    // Status
    // ------------------------------------------------------------------

    /** Issued (approved), not voided, and still carrying a balance. */
    public function isOpen(): bool
    {
        return $this->approval_status === '2'
            && !$this->voided_at
            && in_array($this->payment_status, ['unpaid', 'partially_paid'], true);
    }

    public function scopeOpen($query)
    {
        return $query->where('approval_status', '2')
            ->whereNull('voided_at')
            ->whereIn('payment_status', ['unpaid', 'partially_paid']);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_date && $this->due_date->isPast();
    }

    /** Draft or returned — both can be edited and re-submitted. Rejected ('3') is intentionally excluded. */
    public function isEditable(): bool
    {
        return in_array($this->approval_status, ['0', '4'], true);
    }

    public function statusLabel(): string
    {
        return match ($this->approval_status) {
            '2' => 'Approved',
            '1' => 'Pending Approval',
            '4' => 'Returned',
            '3' => 'Rejected',
            default => 'Draft',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->approval_status) {
            '2' => 'bg-green-100 text-green-700',
            '1' => 'bg-yellow-100 text-yellow-700',
            '4' => 'bg-orange-100 text-orange-700',
            '3' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function paymentStatusLabel(): string
    {
        if ($this->voided_at) {
            return 'Voided';
        }
        if ($this->approval_status !== '2') {
            return 'Not yet issued';
        }

        return match ($this->payment_status) {
            'paid' => 'Paid',
            'partially_paid' => 'Partially Paid',
            default => 'Unpaid',
        };
    }

    public function paymentStatusBadgeClass(): string
    {
        if ($this->voided_at) {
            return 'bg-red-100 text-red-700';
        }
        if ($this->approval_status !== '2') {
            return 'bg-gray-100 text-gray-600';
        }

        return match ($this->payment_status) {
            'paid' => 'bg-green-100 text-green-700',
            'partially_paid' => 'bg-yellow-100 text-yellow-700',
            default => 'bg-blue-100 text-blue-700',
        };
    }
}