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

class Task extends Model implements AuditableContract
{
  use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait, HasCRMCode;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'crm_tasks';

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'code',
    'title',
    'description',
    'due_date',
    'completed_at',
    'reminder_at',
    'task_status_id',
    'task_priority_id',
    'assigned_to_user_id',
    'taskable_id',
    'taskable_type',
    'estimated_hours',
    'actual_hours',
    'task_order',
    'parent_task_id',
    'is_milestone',
    'time_started_at',
    'completed_by',
    'tenant_id',
    'created_by_id',
    'updated_by_id',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'due_date' => 'datetime',
    'completed_at' => 'datetime',
    'reminder_at' => 'datetime',
    'time_started_at' => 'datetime',
    'task_status_id' => 'integer',
    'task_priority_id' => 'integer',
    'assigned_to_user_id' => 'integer',
    'taskable_id' => 'integer',
    'estimated_hours' => 'decimal:2',
    'actual_hours' => 'decimal:2',
    'task_order' => 'integer',
    'parent_task_id' => 'integer',
    'is_milestone' => 'boolean',
    'completed_by' => 'integer',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  /**
   * Get the status of the task.
   */
  public function status() // Renamed to avoid conflict if a 'task_status' column existed
  {
    return $this->belongsTo(TaskStatus::class, 'task_status_id');
  }

  /**
   * Get the priority of the task.
   */
  public function priority() // Renamed for similar reasons
  {
    return $this->belongsTo(TaskPriority::class, 'task_priority_id');
  }

  /**
   * Get the user this task is assigned to.
   */
  public function assignedToUser()
  {
    return $this->belongsTo(User::class, 'assigned_to_user_id');
  }

  /**
   * Get the user who completed this task.
   */
  public function completedBy()
  {
    return $this->belongsTo(User::class, 'completed_by');
  }

  /**
   * Get the parent taskable model (Contact, Company, Lead, or Deal).
   */
  public function taskable()
  {
    return $this->morphTo();
  }

  /**
   * Get the project this task belongs to (via polymorphic relationship).
   */
  public function project()
  {
    if ($this->taskable_type === 'Modules\PMCore\app\Models\Project') {
      return $this->taskable();
    }
    return null;
  }

  /**
   * Get the parent task (for task hierarchies).
   */
  public function parentTask()
  {
    return $this->belongsTo(self::class, 'parent_task_id');
  }

  /**
   * Get the child tasks (for task hierarchies).
   */
  public function childTasks()
  {
    return $this->hasMany(self::class, 'parent_task_id');
  }

  /**
   * Get all descendant tasks (recursive).
   */
  public function descendants()
  {
    return $this->childTasks()->with('descendants');
  }

  /**
   * Check if this task is a milestone.
   */
  public function isMilestone()
  {
    return $this->is_milestone;
  }

  /**
   * Check if this task is completed.
   */
  public function isCompleted()
  {
    return !is_null($this->completed_at);
  }

  /**
   * Mark task as completed.
   */
  public function markAsCompleted()
  {
    $this->update(['completed_at' => now()]);
  }

  /**
   * Mark task as not completed.
   */
  public function markAsNotCompleted()
  {
    $this->update(['completed_at' => null]);
  }

  /**
   * Scope for project tasks using polymorphic relationship.
   */
  public function scopeForProject($query, $projectId)
  {
    return $query->where('taskable_type', 'Modules\PMCore\app\Models\Project')
      ->where('taskable_id', $projectId);
  }

  /**
   * Scope for root tasks (no parent).
   */
  public function scopeRootTasks($query)
  {
    return $query->whereNull('parent_task_id');
  }

  /**
   * Scope for milestone tasks.
   */
  public function scopeMilestones($query)
  {
    return $query->where('is_milestone', true);
  }

  /**
   * Scope for completed tasks.
   */
  public function scopeCompleted($query)
  {
    return $query->whereNotNull('completed_at');
  }

  /**
   * Scope for pending tasks.
   */
  public function scopePending($query)
  {
    return $query->whereNull('completed_at');
  }

  protected static function newFactory()
  {
    return \Modules\CRMCore\Database\factories\TaskFactory::new();
  }

  // createdBy() and updatedBy() relationships are handled by UserActionsTrait
}
