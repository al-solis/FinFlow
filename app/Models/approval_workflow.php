<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
use App\Models\approval_workflow_step;
use App\Models\approval_transaction;

class approval_workflow extends Model
{
    protected $table = 'approval_workflows';

    protected $fillable = [
        'organization_id',
        'module_code',
        'name',
        'description',
        'min_amount',
        'max_amount',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class);
    }

    public function steps()
    {
        return $this->hasMany(approval_workflow_step::class, 'approval_workflow_id')
            ->orderBy('step_no');
    }

    public function transactions()
    {
        return $this->hasMany(approval_transaction::class, 'approval_workflow_id');
    }

    /**
     * Human readable label for the module this workflow governs.
     * Extend this list as new modules are added.
     */
    public static function moduleOptions(): array
    {
        return [
            'rfd' => 'Request for Disbursement',
            // 'payment' => 'Payment Request',
            'ca' => 'Cash Advance Request',
            'liquidation' => 'Cash Advance Liquidation',
            'refund' => 'Refund Request',
            'reimbursement' => 'Reimbursement Request',
        ];
    }

    public function moduleLabel(): string
    {
        return static::moduleOptions()[$this->module_code] ?? $this->module_code;
    }

    /**
     * Find the active workflow that matches a module + amount.
     * Falls back to a workflow with no amount range set (a catch-all).
     */
    public static function resolveFor(string $moduleCode, float $amount, ?int $organizationId = null)
    {
        return static::query()
            ->where('module_code', $moduleCode)
            ->where('is_active', true)
            ->when($organizationId, fn($q) => $q->where(function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
                // ->orWhereNull('organization_id');
            }))
            ->where(function ($q) use ($amount) {
                $q->whereRaw('ISNULL(min_amount, 0) = 0')->orWhere('min_amount', '<=', $amount);
            })
            ->where(function ($q) use ($amount) {
                $q->whereRaw('ISNULL(max_amount, 0) = 0')->orWhere('max_amount', '>=', $amount);
            })
            ->orderByRaw('ISNULL(min_amount, 0), ISNULL(max_amount, 0)') // prefer the more specific range first
            ->first();
    }
}
