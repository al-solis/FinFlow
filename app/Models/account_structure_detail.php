<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class account_structure_detail extends Model
{
    protected $table = 'account_structure_details';

    protected $fillable = [
        'account_structure_id',
        'source_type',
        'segment_id',
        'sequence',
        'separator',
    ];

    public function accountStructure()
    {
        return $this->belongsTo(account_structure::class);
    }

    public function segment()
    {
        return $this->belongsTo(segment::class);
    }

    public function getLabelAttribute()
    {
        return $this->source_type === 'main_account' ? 'ACCT' : ($this->segment->code ?? '');
    }

    public function getDisplayNameAttribute()
    {
        return $this->source_type === 'main_account' ? 'GL Account' : ($this->segment->description ?? '');
    }

    public function getCodeLengthLabelAttribute()
    {
        if ($this->source_type === 'main_account') {
            return 'Any length';
        }
        $len = $this->segment->length ?? 0;
        return $len . ' digit' . ($len == 1 ? '' : 's');
    }

    public function getIsActiveAttribute()
    {
        return $this->source_type === 'main_account' ? true : (bool) ($this->segment->status ?? false);
    }
}