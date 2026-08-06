<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\organization;
use App\Models\account_structure;
use App\Models\main_account;
use App\Models\account_type;
use App\Models\account_category;
use App\Models\account_subcategory;
use App\Models\chart_of_account_segment;
use App\Models\bank_account;

class chart_of_account extends Model
{
    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'organization_id',
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

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

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

    public function bankAccounts()
    {
        return $this->hasMany(bank_account::class, 'chart_of_account_id');
    }

    public function getFormattedAccountCodeAttribute()
    {
        $segments = $this->segments()
            ->with('accountStructureDetail')
            ->orderBy('sequence')
            ->get();

        if ($segments->isEmpty()) {
            return $this->account_code;
        }

        $formatted = [];
        foreach ($segments as $index => $segment) {
            $formatted[] = $segment->display_code;

            // Add separator if it exists and it's not the last segment
            if ($index < $segments->count() - 1) {
                $separator = $segment->accountStructureDetail->separator ?? '';
                if ($separator) {
                    $formatted[] = $separator;
                }
            }
        }

        return implode('', $formatted);
    }

}
