<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class debit_memo_application extends Model
{
    protected $table = 'debit_memo_applications';
    protected $guarded = [];

    public function memo()
    {
        return $this->belongsTo(debit_memo::class, 'debit_memo_id');
    }

    public function invoice()
    {
        return $this->belongsTo(sales_invoice::class, 'sales_invoice_id');
    }
}