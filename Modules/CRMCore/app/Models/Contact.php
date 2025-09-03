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

class Contact extends Model implements AuditableContract
{
    use AuditableTrait, HasCRMCode, HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'contacts';

    protected $fillable = [
        'code',
        'first_name',
        'last_name',
        'email_primary',
        'email_secondary',
        'phone_primary',
        'phone_mobile',
        'phone_office',
        'company_id',
        'job_title',
        'department',
        'address_street',
        'address_city',
        'address_state',
        'address_postal_code',
        'address_country',
        'date_of_birth',
        'lead_source_name',
        'description',
        'do_not_email',
        'do_not_call',
        'is_primary_contact_for_company',
        'is_active',
        'converted_to_customer_at',
        'profile_picture',
        'tenant_id',
        'assigned_to_user_id',
        'created_by_id', // From UserActionsTrait
        'updated_by_id', // From UserActionsTrait
    ];

    protected $casts = [
        'company_id' => 'integer',
        'date_of_birth' => 'date',
        'do_not_email' => 'boolean',
        'do_not_call' => 'boolean',
        'is_primary_contact_for_company' => 'boolean',
        'is_active' => 'boolean',
        'converted_to_customer_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the company that this contact belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user this contact is assigned to.
     */
    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get the customer record for this contact.
     */
    public function customer()
    {
        return $this->hasOne(\Modules\CRMCore\app\Models\Customer::class, 'contact_id');
    }

    /**
     * Get the full name of the contact.
     * Accessor example.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the full name for display (alias for getFullNameAttribute)
     */
    public function getFullName(): string
    {
        return $this->getFullNameAttribute();
    }

    /**
     * Get initials from the contact name
     */
    public function getInitials(): string
    {
        $initials = '';
        if ($this->first_name) {
            $initials .= strtoupper(substr($this->first_name, 0, 1));
        }
        if ($this->last_name) {
            $initials .= strtoupper(substr($this->last_name, 0, 1));
        }

        return $initials ?: 'NA';
    }

    /**
     * Check if contact has a profile picture
     */
    public function hasProfilePicture(): bool
    {
        return ! empty($this->profile_picture);
    }

    /**
     * Get profile picture (placeholder for contacts)
     */
    public function getProfilePicture()
    {
        return $this->profile_picture ?? null;
    }

    public function deals()
    {
        return $this->hasMany(Deal::class, 'contact_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'taskable_id');
    }

    protected static function newFactory()
    {
        return \Modules\CRMCore\database\factories\ContactFactory::new();
    }

    /**
     * Get the user who created this contact.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated this contact.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Check if contact has been converted to customer
     */
    public function isConvertedToCustomer()
    {
        return $this->converted_to_customer_at !== null;
    }

    /**
     * Scope for converted contacts
     */
    public function scopeConverted($query)
    {
        return $query->whereNotNull('converted_to_customer_at');
    }

    /**
     * Scope for unconverted contacts
     */
    public function scopeUnconverted($query)
    {
        return $query->whereNull('converted_to_customer_at');
    }

    /**
     * Get conversion rate for contacts
     */
    public static function getConversionRate()
    {
        $total = self::count();
        if ($total === 0) {
            return 0;
        }

        $converted = self::converted()->count();

        return ($converted / $total) * 100;
    }
}
