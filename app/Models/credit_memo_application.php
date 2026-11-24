<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class credit_memo_application extends Model
{
    protected $table = 'credit_memo_applications';
    protected $guarded = [];

    public function memo()
    {
        return $this->belongsTo(credit_memo::class, 'credit_memo_id');
    }

    public function invoice()
    {
        return $this->belongsTo(sales_invoice::class, 'sales_invoice_id');
    }
}