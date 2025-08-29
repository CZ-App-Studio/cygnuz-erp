<?php

namespace Modules\CRMCore\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskPriority extends Model
{
  use HasFactory;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'task_priorities';

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'color',
    'level', // Numeric level for sorting
    'is_default',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'is_default' => 'boolean',
    'level' => 'integer',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get the tasks associated with this priority.
   */
  public function tasks()
  {
    return $this->hasMany(Task::class, 'task_priority_id');
  }
}
