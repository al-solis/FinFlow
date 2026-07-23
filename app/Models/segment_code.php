<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class segment_code extends Model
{
    protected $table = 'segment_codes';

    protected $fillable = [
        'segment_id',
        'code',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function segment()
    {
        return $this->belongsTo(segment::class, 'segment_id');
    }
}
