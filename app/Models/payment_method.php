<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class payment_method extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = [
        'code',
        'name',
        'description',
        'requires_bank',
        'requires_check',
        'requires_reference_no',
        'allow_partial_payment',
        'payment_channel',
        'status',
        'created_by',
        'updated_by',
    ];
}
