<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class bank_reconciliation_line extends Model
{
    protected $table = 'bank_reconciliation_lines';
    protected $guarded = [];
    protected $casts = ['transaction_date' => 'date', 'accepted' => 'boolean'];

    public function import()
    {
        return $this->belongsTo(bank_reconciliation_import::class, 'bank_reconciliation_import_id');
    }

    public function glAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

    public function journal()
    {
        return $this->belongsTo(gl_journal::class, 'gl_journal_id');
    }

    public function isDeposit(): bool
    {
        return (float) $this->amount >= 0;
    }
}
