<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class main_account extends Model
{
    protected $table = 'main_accounts';

    protected $fillable = [
        'code',
        'description',
        'account_type_id',
        'account_category_id',
        'account_subcategory_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function accountType()
    {
        return $this->belongsTo(account_type::class, 'account_type_id');
    }

    public function accountCategory()
    {
        return $this->belongsTo(account_category::class, 'account_category_id');
    }

    public function accountSubcategory()
    {
        return $this->belongsTo(account_subcategory::class, 'account_subcategory_id');
    }
}
