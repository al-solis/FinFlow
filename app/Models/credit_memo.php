<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class credit_memo extends Model
{
    protected $table = 'credit_memos';
    protected $guarded = [];

    protected $casts = [
        'credit_date' => 'date',
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

    public function originalInvoice()
    {
        return $this->belongsTo(sales_invoice::class, 'original_invoice_id');
    }

    public function series()
    {
        return $this->belongsTo(invoice_series::class, 'credit_memo_series_id');
    }

    public function currency()
    {
        return $this->belongsTo(currency::class, 'currency_id');
    }

    public function lines()
    {
        return $this->hasMany(credit_memo_line::class, 'credit_memo_id')->orderBy('line_no');
    }

    public function applications()
    {
        return $this->hasMany(credit_memo_application::class, 'credit_memo_id');
    }

    public function journal()
    {
        return $this->belongsTo(gl_journal::class, 'gl_journal_id');
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

    public function displayNo(): string
    {
        if ($this->credit_memo_no) {
            return $this->credit_memo_no;
        }
        if ($this->relationLoaded('series') && $this->series) {
            return 'Draft — ' . $this->series->code;
        }
        if ($this->credit_memo_series_id) {
            $series = invoice_series::find($this->credit_memo_series_id);
            if ($series) {
                return 'Draft — ' . $series->code;
            }
        }

        return 'Draft #' . $this->id;
    }

    // ------------------------------------------------------------------
    // Totals
    // ------------------------------------------------------------------

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
        $this->amount_unapplied = round($this->total_amount - $this->amount_applied, 2);

        $this->save();
    }

    // ------------------------------------------------------------------
    // Application arithmetic (concurrency-safe)
    // ------------------------------------------------------------------

    /**
     * Apply a portion of this credit memo to an invoice via the collection-like
     * flow: increments `amount_applied`, then reduces the invoice's balance.
     *
     * Caller must be inside DB::transaction().
     */
    public function applyToInvoice(sales_invoice $invoice, float $amount): void
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return;
        }

        // Track on the memo (atomic increment)
        DB::table('credit_memos')->where('id', $this->id)->update([
            'amount_applied' => DB::raw('amount_applied + ' . $amount),
            'amount_unapplied' => DB::raw('amount_unapplied - ' . $amount),
        ]);

        // Reduce the invoice's AR
        $invoice->applyCreditMemo($amount);

        $this->refresh();
    }

    /**
     * Reverse a previously applied amount (used by void).
     */
    public function reverseApplication(sales_invoice $invoice, float $amount): void
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return;
        }

        DB::table('credit_memos')->where('id', $this->id)->update([
            'amount_applied' => DB::raw('amount_applied - ' . $amount),
            'amount_unapplied' => DB::raw('amount_unapplied + ' . $amount),
        ]);

        $invoice->reverseCreditMemo($amount);

        $this->refresh();
    }

    // ------------------------------------------------------------------
    // Status
    // ------------------------------------------------------------------

    public function isEditable(): bool
    {
        return in_array($this->approval_status, ['0', '4'], true);
    }

    public function isPending(): bool
    {
        return $this->approval_status === '1';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === '2' && !$this->voided_at;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === '3';
    }

    public function isReturned(): bool
    {
        return $this->approval_status === '4';
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function statusLabel(): string
    {
        if ($this->voided_at) {
            return 'Voided';
        }

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
        if ($this->voided_at) {
            return 'bg-red-100 text-red-700';
        }

        return match ($this->approval_status) {
            '2' => 'bg-green-100 text-green-700',
            '1' => 'bg-yellow-100 text-yellow-700',
            '4' => 'bg-orange-100 text-orange-700',
            '3' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }
}