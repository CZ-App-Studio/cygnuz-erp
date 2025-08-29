<?php

namespace Modules\CRMCore\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStatus extends Model
{
  use HasFactory;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'task_statuses';

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'color',
    'position',
    'is_default',
    'is_completed_status',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'is_default' => 'boolean',
    'is_completed_status' => 'boolean',
    'position' => 'integer',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get the tasks associated with this status.
   */
  public function tasks()
  {
    return $this->hasMany(Task::class, 'task_status_id');
  }
}
