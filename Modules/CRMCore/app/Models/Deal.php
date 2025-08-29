<?php

namespace Modules\CRMCore\app\Models;

use App\Models\User;
use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CRMCore\app\Traits\HasCRMCode;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Deal extends Model implements AuditableContract
{
  use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait, HasCRMCode;

  protected $table = 'deals';

  protected $fillable = [
    'code',
    'title',
    'description',
    'value',
    'currency',
    'expected_close_date',
    'actual_close_date',
    'probability',
    'pipeline_id',
    'deal_stage_id',
    'company_id',
    'contact_id',
    'assigned_to_user_id',
    'lost_reason',
    'won_at',
    'lost_at',
    'closed_at',
    'tenant_id',
    'created_by_id',
    'updated_by_id',
  ];

  protected $casts = [
    'value' => 'decimal:2',
    'expected_close_date' => 'date',
    'actual_close_date' => 'date',
    'probability' => 'integer',
    'pipeline_id' => 'integer',
    'deal_stage_id' => 'integer',
    'company_id' => 'integer',
    'contact_id' => 'integer',
    'assigned_to_user_id' => 'integer',
    'won_at' => 'datetime',
    'lost_at' => 'datetime',
    'closed_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  /**
   * Get the pipeline this deal belongs to.
   */
  public function pipeline()
  {
    return $this->belongsTo(DealPipeline::class, 'pipeline_id');
  }

  /**
   * Get the current stage of the deal.
   */
  public function dealStage()
  {
    return $this->belongsTo(DealStage::class);
  }
  
  /**
   * Alias for dealStage relationship
   */
  public function stage()
  {
    return $this->belongsTo(DealStage::class, 'deal_stage_id');
  }

  /**
   * Get the company associated with this deal.
   */
  public function company()
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get the primary contact for this deal.
   */
  public function contact()
  {
    return $this->belongsTo(Contact::class);
  }

  /**
   * Get the user this deal is assigned to.
   */
  public function assignedToUser()
  {
    return $this->belongsTo(User::class, 'assigned_to_user_id');
  }
  
  /**
   * Alias for assignedToUser relationship
   */
  public function assignedTo()
  {
    return $this->belongsTo(User::class, 'assigned_to_user_id');
  }
  
  /**
   * Get the lead this deal was converted from.
   */
  public function lead()
  {
    return $this->hasOne(Lead::class, 'converted_to_deal_id');
  }

  public function tasks()
  {
    return $this->morphMany(Task::class, 'taskable');
  }

  protected static function newFactory()
  {
    return \Modules\CRMCore\database\factories\DealFactory::new();
  }

  /**
   * Get the user who created this deal.
   */
  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by_id');
  }

  /**
   * Get the user who last updated this deal.
   */
  public function updatedBy()
  {
    return $this->belongsTo(User::class, 'updated_by_id');
  }
}
