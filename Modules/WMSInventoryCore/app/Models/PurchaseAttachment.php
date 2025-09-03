<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseAttachment extends Model
{
    protected $fillable = [
        'purchase_id',
        'file_path',
    ];

    /**
     * Get the purchase that owns this attachment.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the file name from the path.
     */
    public function getFileNameAttribute()
    {
        return basename($this->file_path);
    }
}
