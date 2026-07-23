<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class account_subcategory extends Model
{
    protected $table = 'account_subcategories';

    protected $fillable = [
        'account_category_id',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function accountCategory()
    {
        return $this->belongsTo(account_category::class, 'account_category_id');
    }
}
