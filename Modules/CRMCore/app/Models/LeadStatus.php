<?php

namespace Modules\CRMCore\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
  use HasFactory;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'lead_statuses';

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
    'is_final',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'is_default' => 'boolean',
    'is_final' => 'boolean',
    'position' => 'integer',
  ];

  /**
   * Get the leads associated with this status.
   */
  public function leads()
  {
    return $this->hasMany(Lead::class);
  }
}
