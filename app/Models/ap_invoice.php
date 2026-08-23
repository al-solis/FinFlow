<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ap_invoice extends Model
{
    protected $table = 'ap_invoices';
    protected $guarded = [];
    protected $casts = ['invoice_date' => 'date', 'due_date' => 'date'];

    public function vendor()
    {
        return $this->belongsTo(vendor::class);
    }
    public function lines()
    {
        return $this->hasMany(ap_invoice_line::class);
    }
    public function journal()
    {
        return $this->belongsTo(gl_journal::class, 'gl_journal_id');
    }
    public function source()
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }
    public function paymentApplications()
    {
        return $this->hasMany(ap_payment_application::class);
    }

    public function applyPayment(float $amount): void
    {
        $this->amount_paid += $amount;
        $this->amount_due = $this->net_amount - $this->amount_paid;
        $this->status = $this->amount_due <= 0.01 ? 'paid' : 'partially_paid';
        $this->save();
    }
}