<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ap_payment_application extends Model
{
    protected $table = 'ap_payment_applications';
    protected $guarded = [];

    public function payment()
    {
        return $this->belongsTo(ap_payment::class, 'ap_payment_id');
    }
    public function invoice()
    {
        return $this->belongsTo(ap_invoice::class, 'ap_invoice_id');
    }
}