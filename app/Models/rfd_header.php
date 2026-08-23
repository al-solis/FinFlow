<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\organization;
use App\Models\term;
use App\Models\payment_method;
use App\Models\currency;
use App\Models\rfd_detail;
use App\Models\approval_transaction;
use App\Models\rfd_attachment;
use App\Models\ap_invoice;
use App\Models\User;
class rfd_header extends Model
{
    protected $table = 'rfd_headers';

    protected $fillable = [
        'organization_id',
        'request_date',
        'required_date',
        'term_id',
        'payment_method_id',
        'currency_id',
        'exchange_rate',
        'total_amount',
        'total_tax',
        'total_discount',
        'total_due',
        'remarks',
        'status',
        'approval_status',
        'approved_by',
        'submitted_at',
        'submitted_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
        'submitted_at' => 'datetime',
        'exchange_rate' => 'decimal:4',
        'total_amount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_due' => 'decimal:2',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class);
    }

    public function term()
    {
        return $this->belongsTo(term::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(payment_method::class, 'payment_method_id');
    }

    public function currency()
    {
        return $this->belongsTo(currency::class);
    }

    public function details()
    {
        return $this->hasMany(rfd_detail::class, 'rfd_header_id')->orderBy('line_no');
    }

    public function approvalTransactions()
    {
        return $this->morphMany(approval_transaction::class, 'approvable');
    }

    public function latestApprovalTransaction()
    {
        return $this->morphOne(approval_transaction::class, 'approvable')->latestOfMany();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(rfd_attachment::class, 'rfd_header_id');
    }

    /**
     * Vendor is now per line — this gives a display-friendly summary
     * for list views ("Acme Corp" or "Acme Corp +2 more").
     * Call with details.vendor eager loaded to avoid N+1.
     */
    public function vendorSummary(): string
    {
        $names = $this->details->pluck('vendor.name')->filter()->unique()->values();

        if ($names->isEmpty()) {
            return '—';
        }

        if ($names->count() === 1) {
            return $names->first();
        }

        return $names->first() . ' +' . ($names->count() - 1) . ' more';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->approval_status) {
            '2' => 'bg-green-100 text-green-700',
            '1' => 'bg-yellow-100 text-yellow-700',
            '4' => 'bg-orange-100 text-orange-700',
            '3' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600', // draft
        };
    }

    public function statusLabel(): string
    {
        //0 = Draft, 1 = Submitted, 2 = Approved, 3 = Rejected, 4 = Returned
        return match ($this->approval_status) {
            '2' => 'Approved',
            '1' => 'Pending Approval',
            '4' => 'Returned',
            '3' => 'Rejected',
            default => 'Draft',
        };
    }

    public function paymentStatusLabel(): string
    {
        //0 = Unpaid, 1 = Paid, 2 = Partially Paid
        return match ($this->payment_status) {
            '1' => 'Paid',
            '2' => 'Partially Paid',
            default => 'Unpaid',
        };
    }

    public function paymentStatusBadgeClass(): string
    {
        return match ($this->payment_status) {
            '1' => 'bg-green-100 text-green-700',
            '2' => 'bg-yellow-100 text-yellow-700',
            default => 'bg-gray-100 text-gray-600', // Unpaid
        };
    }

    public function apInvoices()
    {
        return $this->morphMany(ap_invoice::class, 'source', 'source_type', 'source_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
