<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class chart_of_account_segment extends Model
{
    protected $table = 'chart_of_account_segments';

    protected $fillable = [
        'chart_of_account_id',
        'account_structure_detail_id',
        'segment_id',
        'segment_value_id',
        'display_code',
        'display_name',
        'sequence',
    ];

    public function chartOfAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'chart_of_account_id');
    }

    public function accountStructureDetail()
    {
        return $this->belongsTo(account_structure_detail::class, 'account_structure_detail_id');
    }

    public function segment()
    {
        return $this->belongsTo(segment::class, 'segment_id');
    }


}
