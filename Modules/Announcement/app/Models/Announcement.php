<?php

namespace Modules\Announcement\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Modules\HRCore\app\Models\Department;
use Modules\HRCore\app\Models\Team;
use Carbon\Carbon;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'content',
        'priority',
        'type',
        'target_audience',
        'send_email',
        'send_notification',
        'is_pinned',
        'requires_acknowledgment',
        'publish_date',
        'expiry_date',
        'status',
        'attachment',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'send_email' => 'boolean',
        'send_notification' => 'boolean',
        'is_pinned' => 'boolean',
        'requires_acknowledgment' => 'boolean',
        'publish_date' => 'datetime',
        'expiry_date' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($announcement) {
            if (!$announcement->created_by) {
                $announcement->created_by = auth()->id();
            }
        });

        static::updating(function ($announcement) {
            $announcement->updated_by = auth()->id();
        });

        // Auto-update status based on dates
        static::saving(function ($announcement) {
            if ($announcement->status !== 'draft' && $announcement->status !== 'archived') {
                $now = Carbon::now();
                
                if ($announcement->expiry_date && $announcement->expiry_date->isPast()) {
                    $announcement->status = 'expired';
                } elseif ($announcement->publish_date && $announcement->publish_date->isFuture()) {
                    $announcement->status = 'scheduled';
                } elseif ($announcement->publish_date && $announcement->publish_date->isPast()) {
                    $announcement->status = 'published';
                }
            }
        });
    }

    /**
     * Get the creator of the announcement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater of the announcement.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the departments for the announcement.
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'announcement_departments')
                    ->withTimestamps();
    }

    /**
     * Get the teams for the announcement.
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'announcement_teams')
                    ->withTimestamps();
    }

    /**
     * Get the specific users for the announcement.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'announcement_users')
                    ->withTimestamps();
    }

    /**
     * Get the read records for the announcement.
     */
    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    /**
     * Get users who have read the announcement.
     */
    public function readBy()
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
                    ->withPivot(['read_at', 'acknowledged', 'acknowledged_at'])
                    ->withTimestamps();
    }

    /**
     * Scope for published announcements.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where(function ($q) {
                         $q->whereNull('publish_date')
                           ->orWhere('publish_date', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('expiry_date')
                           ->orWhere('expiry_date', '>', now());
                     });
    }

    /**
     * Scope for active announcements (published and not expired).
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();
        return $query->where(function ($q) use ($now) {
            $q->where('status', 'published')
              ->orWhere(function ($q2) use ($now) {
                  $q2->where('status', 'scheduled')
                     ->where('publish_date', '<=', $now);
              });
        })->where(function ($q) use ($now) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>', $now);
        });
    }

    /**
     * Scope for pinned announcements.
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope for priority-based ordering.
     */
    public function scopeByPriority($query)
    {
        return $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')");
    }

    /**
     * Get announcements for a specific user based on their department and team.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // All employees
            $q->where('target_audience', 'all');
              
            // Specific departments (through designation)
            if ($user->designation_id && $user->designation && $user->designation->department_id) {
                $q->orWhere(function ($q2) use ($user) {
                    $q2->where('target_audience', 'departments')
                       ->whereHas('departments', function ($q3) use ($user) {
                           $q3->where('departments.id', $user->designation->department_id);
                       });
                });
            }
            
            // Specific teams
            if ($user->team_id) {
                $q->orWhere(function ($q2) use ($user) {
                    $q2->where('target_audience', 'teams')
                       ->whereHas('teams', function ($q3) use ($user) {
                           $q3->where('teams.id', $user->team_id);
                       });
                });
            }
            
            // Specific users
            $q->orWhere(function ($q2) use ($user) {
                $q2->where('target_audience', 'specific_users')
                   ->whereHas('users', function ($q3) use ($user) {
                       $q3->where('users.id', $user->id);
                   });
            });
        });
    }

    /**
     * Check if the announcement has been read by a user.
     */
    public function isReadBy(User $user)
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if the announcement has been acknowledged by a user.
     */
    public function isAcknowledgedBy(User $user)
    {
        return $this->reads()
                    ->where('user_id', $user->id)
                    ->where('acknowledged', true)
                    ->exists();
    }

    /**
     * Mark as read by a user.
     */
    public function markAsReadBy(User $user)
    {
        if (!$this->isReadBy($user)) {
            AnnouncementRead::create([
                'announcement_id' => $this->id,
                'user_id' => $user->id,
                'read_at' => now()
            ]);
        }
    }

    /**
     * Mark as acknowledged by a user.
     */
    public function markAsAcknowledgedBy(User $user)
    {
        $read = $this->reads()->where('user_id', $user->id)->first();
        
        if ($read) {
            $read->update([
                'acknowledged' => true,
                'acknowledged_at' => now()
            ]);
        } else {
            AnnouncementRead::create([
                'announcement_id' => $this->id,
                'user_id' => $user->id,
                'read_at' => now(),
                'acknowledged' => true,
                'acknowledged_at' => now()
            ]);
        }
    }

    /**
     * Get the read percentage for the announcement.
     */
    public function getReadPercentageAttribute()
    {
        $totalUsers = $this->getTotalTargetUsers();
        if ($totalUsers === 0) {
            return 0;
        }

        $readCount = $this->reads()->count();
        return round(($readCount / $totalUsers) * 100, 2);
    }

    /**
     * Get total number of target users.
     */
    public function getTotalTargetUsers()
    {
        switch ($this->target_audience) {
            case 'all':
                return User::count();
            
            case 'departments':
                // Count users whose designation belongs to the selected departments
                $departmentIds = $this->departments->pluck('id');
                return User::whereHas('designation', function ($q) use ($departmentIds) {
                    $q->whereIn('department_id', $departmentIds);
                })->count();
            
            case 'teams':
                return User::whereIn('team_id', $this->teams->pluck('id'))->count();
            
            case 'specific_users':
                return $this->users()->count();
            
            default:
                return 0;
        }
    }
}