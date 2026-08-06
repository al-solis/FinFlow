<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
class payment_method extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = [
        'organization_id',
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

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }
}
