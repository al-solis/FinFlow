<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class rfd_attachment extends Model
{
    protected $table = 'rfd_attachments';

    protected $fillable = [
        'rfd_header_id',
        'file_name',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'description',
        'uploaded_by',
    ];

    public function rfdHeader(): BelongsTo
    {
        return $this->belongsTo(rfd_header::class, 'rfd_header_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}