<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class segment extends Model
{
    protected $table = 'segments';

    protected $fillable = [
        'code',
        'description',
        'length',
        'status',
        'created_by',
        'updated_by',
    ];

    public function segmentCodes()
    {
        return $this->hasMany(segment_code::class, 'segment_id');
    }


}
