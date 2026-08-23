<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\approval_workflow;
use App\Models\role;
use App\Models\User;

class approval_workflow_step extends Model
{
    protected $table = 'approval_workflow_steps';

    protected $fillable = [
        'approval_workflow_id',
        'step_no',
        'step_name',
        'approver_type',
        'role_id',
        'user_id',
        'can_edit_chart_of_account',
        'can_edit_tax',
        'can_edit_amount',
        'can_return_to_requester',
        'is_final_approval',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'can_edit_chart_of_account' => 'boolean',
        'can_edit_tax' => 'boolean',
        'can_edit_amount' => 'boolean',
        'can_return_to_requester' => 'boolean',
        'is_final_approval' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(approval_workflow::class, 'approval_workflow_id');
    }

    // Adjust these two if your app's role/user models differ
    public function role()
    {
        return $this->belongsTo(role::class, 'role_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approverLabel(): string
    {
        if ($this->approver_type === 'user') {
            return $this->user->last_name . ', ' . $this->user->first_name . ' ' . $this->user->middle_name ?? '—';
        }

        return $this->role->name ?? '—';
    }
}
