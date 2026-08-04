<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class vendor_attachment extends Model
{
    protected $table = 'vendor_attachments';

    protected $fillable = [
        'vendor_id',
        'filename',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'description',
        'created_by',
        'updated_by',
    ];

    public function vendor()
    {
        return $this->belongsTo(vendor::class);
    }
}
