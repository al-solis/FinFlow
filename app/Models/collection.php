<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class collection extends Model
{
    protected $table = 'collections';
    protected $guarded = [];

    protected $casts = [
        'collection_date' => 'date',
        'check_date' => 'date',
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

    public function paymentMethod()
    {
        return $this->belongsTo(payment_method::class, 'payment_method_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(bank_account::class, 'bank_account_id');
    }

    public function currency()
    {
        return $this->belongsTo(currency::class, 'currency_id');
    }

    public function cwtTax()
    {
        return $this->belongsTo(tax_master::class, 'cwt_tax_id');
    }

    public function advanceAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'advance_account_id');
    }

    public function applications()
    {
        return $this->hasMany(collection_application::class, 'collection_id');
    }

    public function journal()
    {
        return $this->belongsTo(gl_journal::class, 'gl_journal_id');
    }

    public function whtCertificates()
    {
        return $this->hasMany(wht_certificate::class, 'collection_id');
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

    /** Human-readable identifier. The number is issued from the series on submit; drafts have none. */
    public function displayNo(): string
    {
        return $this->collection_no ?: ('Draft #' . $this->id);
    }

    // ------------------------------------------------------------------
    // Approval state
    // ------------------------------------------------------------------

    public function isEditable(): bool
    {
        return in_array($this->approval_status, ['0', '4'], true);
    }

    public function isSubmitted(): bool
    {
        return $this->approval_status === '1';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === '2';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === '3';
    }

    public function isReturned(): bool
    {
        return $this->approval_status === '4';
    }

    public function approvalStatusLabel(): string
    {
        return match ($this->approval_status) {
            '2' => 'Approved',
            '1' => 'Pending Approval',
            '4' => 'Returned',
            '3' => 'Rejected',
            default => 'Draft',
        };
    }

    public function approvalStatusBadgeClass(): string
    {
        return match ($this->approval_status) {
            '2' => 'bg-green-100 text-green-700',
            '1' => 'bg-yellow-100 text-yellow-700',
            '4' => 'bg-orange-100 text-orange-700',
            '3' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    // ------------------------------------------------------------------
    // Settlement state (only meaningful once approval_status = '2')
    // ------------------------------------------------------------------

    public function isPosted(): bool
    {
        return $this->status === 'posted' && !$this->voided_at;
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided' || $this->voided_at !== null;
    }

    public function settlementStatusLabel(): string
    {
        if ($this->voided_at) {
            return 'Voided';
        }
        if ($this->approval_status !== '2') {
            return 'Not yet approved';
        }

        return match ($this->status) {
            'posted' => 'Posted',
            'bounced' => 'Bounced',
            'voided' => 'Voided',
            default => ucfirst($this->status),
        };
    }

    public function settlementStatusBadgeClass(): string
    {
        if ($this->voided_at) {
            return 'bg-red-100 text-red-700';
        }
        if ($this->approval_status !== '2') {
            return 'bg-gray-100 text-gray-600';
        }

        return match ($this->status) {
            'posted' => 'bg-green-100 text-green-700',
            'bounced' => 'bg-orange-100 text-orange-700',
            'voided' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    /** Legacy accessor kept for existing Blade usage (index/show). */
    public function statusBadgeClass(): string
    {
        return $this->settlementStatusBadgeClass();
    }

    // ------------------------------------------------------------------
    // Settlement arithmetic
    // ------------------------------------------------------------------

    /**
     * Recompute derived totals from the applications.
     * Called after saving/deleting applications.
     */
    public function recalcApplied(): void
    {
        $applications = $this->applications()->get();
        $appliedTotal = round((float) $applications->sum('amount_applied'), 2);
        $cwtAppliedTotal = round((float) $applications->sum('cwt_applied'), 2);

        $this->amount_applied = $appliedTotal;
        $this->amount_unapplied = round((float) $this->amount_received - $appliedTotal, 2);
        // Keep the header CWT in step with what is actually allocated to invoices; posting
        // requires cwt_amount == sum(cwt_applied), so an approver edit must update both.
        $this->cwt_amount = $cwtAppliedTotal;
        $this->save();
    }

    /**
     * Restore `amount_paid` and `cwt_applied` on every applied invoice.
     * Used when the collection is voided.
     *
     * Caller must be inside a DB::transaction.
     */
    public function reverseInvoiceApplications(): void
    {
        foreach ($this->applications as $app) {
            $app->invoice?->reverseSettlement(
                (float) $app->amount_applied,
                (float) $app->cwt_applied
            );
        }
    }
}