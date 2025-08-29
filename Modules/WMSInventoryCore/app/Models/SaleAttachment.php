<?php

namespace Modules\WMSInventoryCore\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleAttachment extends Model
{
    use SoftDeletes, UserActionsTrait;
    
    protected $fillable = [
        'sale_id',
        'file_path',
        'created_by_id',
        'updated_by_id'
    ];

    /**
     * Get the sale that owns this attachment.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the file name from the path.
     */
    public function getFileNameAttribute()
    {
        return basename($this->file_path);
    }
}
