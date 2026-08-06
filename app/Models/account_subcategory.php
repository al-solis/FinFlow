<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\account_category;
use App\Models\organization;

class account_subcategory extends Model
{
    protected $table = 'account_subcategories';

    protected $fillable = [
        'organization_id',
        'account_category_id',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }
    public function accountCategory()
    {
        return $this->belongsTo(account_category::class, 'account_category_id');
    }
}
