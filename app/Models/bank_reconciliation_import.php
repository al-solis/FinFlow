<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class bank_reconciliation_import extends Model
{
    protected $table = 'bank_reconciliation_imports';
    protected $guarded = [];
    protected $casts = ['statement_from' => 'date', 'statement_to' => 'date', 'posted_at' => 'datetime'];

    public function bankAccount()
    {
        return $this->belongsTo(bank_account::class, 'bank_account_id');
    }

    public function lines()
    {
        return $this->hasMany(bank_reconciliation_line::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
