<?php

namespace Modules\CRMCore\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealStage extends Model
{
    use HasFactory;

    protected $table = 'deal_stages';

    protected $fillable = [
        'pipeline_id',
        'name',
        'color',
        'position',
        'is_default_for_pipeline',
        'is_won_stage',
        'is_lost_stage',
    ];

    protected $casts = [
        'pipeline_id' => 'integer',
        'position' => 'integer',
        'is_default_for_pipeline' => 'boolean',
        'is_won_stage' => 'boolean',
        'is_lost_stage' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the pipeline this stage belongs to.
     */
    public function pipeline()
    {
        return $this->belongsTo(DealPipeline::class, 'pipeline_id');
    }

    /**
     * Get all deals currently in this stage.
     */
    public function deals()
    {
        return $this->hasMany(Deal::class);
    }
}
