<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class collection_application extends Model
{
    protected $table = 'collection_applications';
    protected $guarded = [];

    public function collection()
    {
        return $this->belongsTo(collection::class, 'collection_id');
    }

    public function invoice()
    {
        return $this->belongsTo(sales_invoice::class, 'sales_invoice_id');
    }
}
