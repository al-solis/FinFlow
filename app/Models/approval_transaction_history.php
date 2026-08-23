<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\approval_transaction;
use App\Models\approval_workflow_step;

class approval_transaction_history extends Model
{
    protected $table = 'approval_transaction_histories';

    protected $fillable = [
        'approval_transaction_id',
        'approval_workflow_step_id',
        'step_no',
        'action',
        'actor_id',
        'remarks',
        'acted_at',
        'created_by',
    ];

    public function transaction()
    {
        return $this->belongsTo(approval_transaction::class, 'approval_transaction_id');
    }

    public function step()
    {
        return $this->belongsTo(approval_workflow_step::class, 'approval_workflow_step_id');
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_id');
    }
}
