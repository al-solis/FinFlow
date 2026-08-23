<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\approval_workflow;
use App\Models\approval_transaction_history;
use App\Models\User;
use App\Models\rfd_header;

class approval_transaction extends Model
{
    protected $table = 'approval_transactions';

    protected $fillable = [
        'approval_workflow_id',
        'approvable_type',
        'approvable_id',
        'current_step_no',
        'status',
        'created_by',
        'updated_by',
    ];

    public function workflow()
    {
        return $this->belongsTo(approval_workflow::class, 'approval_workflow_id');
    }

    public function approvable()
    {
        return $this->morphTo();
    }

    public function histories()
    {
        return $this->hasMany(approval_transaction_history::class, 'approval_transaction_id')
            ->orderBy('created_at');
    }

    public function currentStep()
    {
        return $this->workflow->steps()->where('step_no', $this->current_step_no)->first();
    }

    public function reviewUrl(): string
    {
        return match ($this->approvable_type) {
            rfd_header::class => route('ap.rfd.showApproval', $this->id),
            default => '#',
        };
    }

    public function scopePendingFor($query, User $user)
    {
        $roleIds = method_exists($user, 'roles') ? $user->roles->pluck('id') : collect();

        return $query->where('approval_transactions.status', 'pending')
            ->join('approval_workflow_steps', function ($join) {
                $join->on('approval_workflow_steps.approval_workflow_id', '=', 'approval_transactions.approval_workflow_id')
                    ->on('approval_workflow_steps.step_no', '=', 'approval_transactions.current_step_no');
            })
            ->where(function ($q) use ($user, $roleIds) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('approval_workflow_steps.approver_type', 'user')
                        ->where('approval_workflow_steps.user_id', $user->id);
                })->orWhere(function ($q2) use ($roleIds) {
                    $q2->where('approval_workflow_steps.approver_type', 'role')
                        ->whereIn('approval_workflow_steps.role_id', $roleIds);
                });
            })
            ->select('approval_transactions.*');
    }
}
