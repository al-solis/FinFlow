<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
use App\Models\account_type;
use App\Models\account_category;
use App\Models\account_subcategory;

class main_account extends Model
{
    protected $table = 'main_accounts';

    protected $fillable = [
        'organization_id',
        'code',
        'description',
        'account_type_id',
        'account_category_id',
        'account_subcategory_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
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
}
