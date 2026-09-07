<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class vendor_bank_account extends Model
{
    protected $table = 'vendor_bank_accounts';

    protected $fillable = [
        'vendor_id',
        'bank_name',
        'branch',
        'account_name',
        'account_number',
        'swift_code',
        'currency_id',
        'is_primary',
        'created_by',
        'updated_by',
    ];

    public const PII_FIELDS = [
        'account_number' => 'bank_account',

    ];

    public function vendor()
    {
        return $this->belongsTo(vendor::class, 'vendor_id');
    }

    public function currency()
    {
        return $this->belongsTo(currency::class, 'currency_id');
    }
}
