<?php
// app/Models/gl_journal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class gl_journal extends Model
{
    protected $table = 'gl_journals';
    protected $guarded = [];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at' => 'datetime',
        'submitted_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public function lines()
    {
        return $this->hasMany(gl_journal_line::class);
    }

    public function reference()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvalTransactions()
    {
        return $this->morphMany(approval_transaction::class, 'approvable');
    }

    public function latestApprovalTransaction()
    {
        return $this->morphOne(approval_transaction::class, 'approvable')->latestOfMany();
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public function statusLabel(): string
    {
        return match ($this->approval_status) {
            '2' => 'Posted',
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

    public function scopePending($query)
    {
        return $query->where('approval_status', '1');
    }

    public function scopePosted($query)
    {
        return $query->where('approval_status', '2');
    }

    public function scopeDraft($query)
    {
        return $query->where('approval_status', '0');
    }
}