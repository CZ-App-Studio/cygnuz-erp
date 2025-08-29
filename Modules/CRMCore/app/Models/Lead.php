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

class Lead extends Model implements AuditableContract
{
  use SoftDeletes, UserActionsTrait, AuditableTrait, HasFactory, HasCRMCode;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'leads';

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'code',
    'title',
    'description',
    'contact_name',
    'contact_email',
    'contact_phone',
    'company_name',
    'value',
    'lead_source_id',
    'lead_status_id',
    'assigned_to_user_id',
    'converted_at',
    'converted_to_contact_id',
    'converted_to_deal_id',
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
    'value' => 'decimal:2',
    'converted_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  /**
   * Get the status of the lead.
   */
  public function leadStatus()
  {
    return $this->belongsTo(LeadStatus::class);
  }
  
  /**
   * Alias for leadStatus relationship
   */
  public function status()
  {
    return $this->belongsTo(LeadStatus::class, 'lead_status_id');
  }

  /**
   * Get the source of the lead.
   */
  public function leadSource()
  {
    return $this->belongsTo(LeadSource::class);
  }
  
  /**
   * Alias for leadSource relationship
   */
  public function source()
  {
    return $this->belongsTo(LeadSource::class, 'lead_source_id');
  }

  /**
   * Get the user this lead is assigned to.
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
   * Get the contact this lead was converted into.
   */
  public function convertedToContact()
  {
    return $this->belongsTo(Contact::class, 'converted_to_contact_id');
  }
  
  /**
   * Alias for convertedToContact relationship
   */
  public function contact()
  {
    return $this->belongsTo(Contact::class, 'converted_to_contact_id');
  }

  /**
   * Get the deal this lead was converted into.
   */
  public function convertedToDeal()
  {
    // Note: Assumes a Deal model will exist.
    return $this->belongsTo(Deal::class, 'converted_to_deal_id');
  }
  
  /**
   * Get the company associated with this lead (if converted to contact with company)
   */
  public function company()
  {
    return $this->hasOneThrough(Company::class, Contact::class, 'id', 'id', 'converted_to_contact_id', 'company_id');
  }

  public function tasks()
  {
    return $this->morphMany(Task::class, 'taskable');
  }

  protected static function newFactory()
  {
    return \Modules\CRMCore\database\factories\LeadFactory::new();
  }

  /**
   * Get the user who created this lead.
   */
  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by_id');
  }

  /**
   * Get the user who last updated this lead.
   */
  public function updatedBy()
  {
    return $this->belongsTo(User::class, 'updated_by_id');
  }
}
