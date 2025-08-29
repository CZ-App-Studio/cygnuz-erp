<?php

namespace Modules\Calendar\app\Models;

use App\Enums\EventType;
use App\Models\User;
use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\FieldManager\app\Models\Client;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Event extends Model implements Auditable
{
  use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait;

  protected $table = 'events';

  /**
   * Create a new factory instance for the model.
   */
  protected static function newFactory()
  {
    return \Modules\Calendar\database\factories\EventFactory::new();
  }

  protected $fillable = [
    'title',
    'description',
    'start',
    'end',
    'all_day',
    'color',
    'event_type',
    'related_type', // Morphs
    'related_id',   // Morphs
    'meeting_link',
    'tenant_id',
    'location',
    'created_by_id',
    'updated_by_id',
  ];

  protected $casts = [
    'start' => 'datetime',
    'end' => 'datetime',
    'all_day' => 'boolean',
    'event_type' => EventType::class,
  ];

  /**
   * The user who created the event.
   */
  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by_id');
  }

  /**
   * The user who last updated the event.
   */
  public function updater(): BelongsTo
  {
    return $this->belongsTo(User::class, 'updated_by_id');
  }

  /**
   * The users attending the event.
   */
  public function attendees(): BelongsToMany
  {
    // Define the relationship using the pivot table 'event_user'
    return $this->belongsToMany(User::class, 'event_user', 'event_id', 'user_id')
      ->withTimestamps(); // Include if you added timestamps to the pivot table
  }

  /**
   * The related entity (can be Company, Contact, Client, Project, etc.)
   */
  public function related()
  {
    return $this->morphTo();
  }

  /**
   * The client associated with the event (if any).
   * @deprecated Use related() instead for better flexibility
   */
  public function client(): BelongsTo
  {
    return $this->belongsTo(Client::class, 'related_id')
      ->where('related_type', Client::class);
  }

  /**
   * Helper method to get company if related entity is a company
   */
  public function company()
  {
    return $this->morphTo('related')->where('related_type', 'Modules\CRMCore\app\Models\Company');
  }

  /**
   * Helper method to get contact if related entity is a contact
   */
  public function contact()
  {
    return $this->morphTo('related')->where('related_type', 'Modules\CRMCore\app\Models\Contact');
  }

  /**
   * Helper method to get project if related entity is a project (for future PMCore integration)
   */
  public function project()
  {
    return $this->morphTo('related')->where('related_type', 'Modules\PMCore\app\Models\Project');
  }

}
