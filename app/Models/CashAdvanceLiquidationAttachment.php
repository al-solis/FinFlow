<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAdvanceLiquidationAttachment extends Model
{
    protected $table = 'cash_advance_liquidation_attachments';

    protected $fillable = [
        'liquidation_id',
        'file_name',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'description',
        'uploaded_by',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the liquidation that owns the attachment.
     */
    public function liquidation(): BelongsTo
    {
        return $this->belongsTo(CashAdvanceLiquidation::class, 'liquidation_id');
    }

    /**
     * Get the user who uploaded the attachment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the creator of the attachment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater of the attachment.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}