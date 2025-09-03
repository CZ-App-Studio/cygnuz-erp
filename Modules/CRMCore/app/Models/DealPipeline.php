<?php

namespace Modules\CRMCore\app\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class DealPipeline extends Model implements AuditableContract
{
    use AuditableTrait, UserActionsTrait;

    protected $table = 'deal_pipelines';

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'is_active',
        'position',
        'tenant_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the stages associated with this pipeline, ordered by position.
     */
    public function stages()
    {
        return $this->hasMany(DealStage::class, 'pipeline_id')->orderBy('position');
    }

    /**
     * Get all deals that belong to this pipeline.
     */
    public function deals()
    {
        return $this->hasMany(Deal::class, 'pipeline_id');
    }

    // createdBy() and updatedBy() relationships handled by UserActionsTrait
}
