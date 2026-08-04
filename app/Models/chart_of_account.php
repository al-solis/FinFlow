<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class chart_of_account extends Model
{
    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'account_structure_id',
        'account_code',
        'account_name',
        'main_account_id',
        'account_type_id',
        'account_category_id',
        'account_subcategory_id',
        'is_posting',
        'status',
        'created_by',
        'updated_by',
    ];

    public function structure()
    {
        return $this->belongsTo(account_structure::class, 'account_structure_id');
    }

    public function mainAccount()
    {
        return $this->belongsTo(main_account::class, 'main_account_id');
    }

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

    public function segments()
    {
        return $this->hasMany(chart_of_account_segment::class, 'chart_of_account_id');
    }
}
