<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\vendor;
use App\Models\bank_account;
use App\Models\gl_journal;
use App\Models\ap_payment_application;
use App\Models\payment_method;

class ap_payment extends Model
{
    protected $table = 'ap_payments';
    protected $guarded = [];
    protected $casts = ['payment_date' => 'date'];

    public function vendor()
    {
        return $this->belongsTo(vendor::class);
    }
    public function bankAccount()
    {
        return $this->belongsTo(bank_account::class, 'bank_account_id');
    }
    public function journal()
    {
        return $this->belongsTo(gl_journal::class, 'gl_journal_id');
    }
    public function applications()
    {
        return $this->hasMany(ap_payment_application::class);
    }
    public function paymentMethod()
    {
        return $this->belongsTo(payment_method::class, 'payment_method_id');
    }
}