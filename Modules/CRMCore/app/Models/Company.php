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

class Company extends Model implements AuditableContract
{
    use AuditableTrait, HasCRMCode, HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'companies';

    protected $fillable = [
        'code',
        'name',
        'website',
        'phone_office',
        'email_office',
        'address_street',
        'address_city',
        'address_state',
        'address_postal_code',
        'address_country',
        'industry',
        'description',
        'is_active',
        'tenant_id',
        'assigned_to_user_id',
        'created_by_id', // From UserActionsTrait
        'updated_by_id', // From UserActionsTrait
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the contacts associated with the company.
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the primary contact for the company.
     */
    public function primaryContact()
    {
        return $this->hasOne(Contact::class)->where('is_primary_contact_for_company', true);
    }

    /**
     * Get the user this company is assigned to.
     */
    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get the leads associated with the company.
     */
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    // Set factory path
    protected static function newFactory()
    {
        return \Modules\CRMCore\database\factories\CompanyFactory::new();
    }

    /**
     * Get the user who created this company.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated this company.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
