<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\rfd_detail;
use App\Models\tax_master;

class rfd_detail_tax extends Model
{
    protected $table = 'rfd_detail_taxes';

    protected $fillable = [
        'rfd_detail_id',
        'line_no',
        'tax_id',
        'taxable_amount',
        'tax_amount',
        'created_by',
        'updated_by'
    ];
    public function rfdDetail()
    {
        return $this->belongsTo(rfd_detail::class, 'rfd_detail_id');
    }

    public function tax()
    {
        return $this->belongsTo(tax_master::class, 'tax_id');
    }
}
