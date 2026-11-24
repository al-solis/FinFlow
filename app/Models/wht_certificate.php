<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class wht_certificate extends Model
{
    protected $table = 'wht_certificates';
    protected $guarded = [];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'certificate_date' => 'date',
        'received_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(customer::class, 'customer_id');
    }

    public function collection()
    {
        return $this->belongsTo(collection::class, 'collection_id');
    }
}
