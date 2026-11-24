<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class invoice_series extends Model
{
    protected $table = 'invoice_series';
    protected $guarded = [];

    protected $casts = [
        'authority_date' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'series_start' => 'integer',
        'series_end' => 'integer',
        'next_number' => 'integer',
        'padding' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

    /** The series a given invoice was issued from. */
    public function salesInvoices()
    {
        return $this->hasMany(sales_invoice::class, 'invoice_series_id');
    }

    // ------------------------------------------------------------------
    // Formatting
    // ------------------------------------------------------------------

    /** Zero-pad $n to $this->padding, prefixed. label(12) => "SI-000012". */
    public function label(int $n): string
    {
        return ($this->prefix ?? '') . str_pad((string) $n, max((int) $this->padding, 1), '0', STR_PAD_LEFT);
    }

    /** The formatted label for the number that the next issued document will receive. */
    public function nextLabel(): string
    {
        return $this->label((int) $this->next_number);
    }

    // ------------------------------------------------------------------
    // Range / lock state
    // ------------------------------------------------------------------

    /**
     * Numbers already issued: any semantic field (document_type, code, prefix,
     * series_start, padding) must not change. The end of the range may still be
     * extended (series_end). Consumers rely on this to lock the form and the
     * server-side rule set.
     */
    public function isLocked(): bool
    {
        return (int) $this->next_number > (int) $this->series_start;
    }

    /** Remaining unissued numbers. Null when the range has no end. */
    public function remaining(): ?int
    {
        if (!$this->series_end) {
            return null;
        }

        return max((int) $this->series_end - (int) $this->next_number + 1, 0);
    }

    /** True when no end is set? No — a series with no end is not "low". Low = finite end and ≤ 10 remaining. */
    public function isLow(int $threshold = 10): bool
    {
        $remaining = $this->remaining();

        return $remaining !== null && $remaining <= $threshold;
    }

    // ------------------------------------------------------------------
    // Validity
    // ------------------------------------------------------------------

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    /** True when valid_until falls on or before today + $days (but not already expired). */
    public function expiresWithin(int $days): bool
    {
        if (!$this->valid_until || $this->isExpired()) {
            return false;
        }

        return $this->valid_until->lte(today()->addDays($days));
    }

    /**
     * Refuse to issue a number when the series is exhausted, expired, or the
     * next number would fall outside the range. Call from the approval workflow
     * inside the same DB transaction that increments next_number.
     *
     * Throws a RuntimeException — the workflow's approval action catches it and
     * rolls back, so the approver sees the reason.
     */
    public function assertIssuable(): void
    {
        if (!$this->is_active) {
            throw new \RuntimeException("Series {$this->code} is inactive.");
        }
        if ($this->isExpired()) {
            throw new \RuntimeException(
                "Series {$this->code} expired on {$this->valid_until->format('M d, Y')}."
            );
        }
        if ($this->series_end && (int) $this->next_number > (int) $this->series_end) {
            throw new \RuntimeException(
                "Series {$this->code} is exhausted (last number {$this->label((int) $this->series_end)})."
            );
        }
    }
}