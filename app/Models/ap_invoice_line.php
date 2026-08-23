<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ap_invoice_line extends Model
{
    protected $table = 'ap_invoice_lines';
    protected $guarded = [];

    public function invoice()
    {
        return $this->belongsTo(ap_invoice::class, 'ap_invoice_id');
    }
    public function glAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }
}