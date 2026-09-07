<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
use App\Models\currency;
use App\Models\chart_of_account;

class bank_account extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'branch',
        'account_name',
        'account_number',
        'currency_id',
        'account_type',
        'chart_of_account_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public const PII_FIELDS = [
        'account_number' => 'bank_account',
        'account_name' => 'full_name',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

    public function currency()
    {
        return $this->belongsTo(currency::class, 'currency_id');
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'chart_of_account_id');
    }

}
