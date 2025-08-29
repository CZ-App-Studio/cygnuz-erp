<?php

namespace Modules\AccountingCore\app\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BasicTransactionCategory extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait;

    protected $table = 'basic_transaction_categories';

    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'icon',
        'color',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BasicTransactionCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(BasicTransactionCategory::class, 'parent_id');
    }

    /**
     * Get all descendant categories recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get the transactions for this category.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BasicTransaction::class, 'category_id');
    }

    /**
     * Get the user who created this category.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this category.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Scope to get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get categories by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get parent categories (no parent_id).
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the full path from root to this category.
     */
    public function getFullPathAttribute(): string
    {
        $path = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $path->implode(' > ');
    }

    /**
     * Check if this category has child categories.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get formatted display with icon.
     */
    public function getDisplayWithIconAttribute(): string
    {
        $icon = $this->icon ? "<i class='{$this->icon}'></i> " : '';
        return $icon . $this->name;
    }

    /**
     * Get badge HTML for display.
     */
    public function getBadgeHtmlAttribute(): string
    {
        $typeClass = $this->type === 'income' ? 'bg-label-success' : 'bg-label-danger';
        return sprintf(
            '<span class="badge %s">%s</span>',
            $typeClass,
            ucfirst($this->type)
        );
    }

    /**
     * Get default categories for seeding.
     */
    public static function getDefaultCategories(): array
    {
        return [
            // Income categories
            ['name' => 'Sales', 'type' => 'income', 'icon' => 'bx bx-dollar', 'color' => '#28a745'],
            ['name' => 'Services', 'type' => 'income', 'icon' => 'bx bx-briefcase', 'color' => '#17a2b8'],
            ['name' => 'Investments', 'type' => 'income', 'icon' => 'bx bx-trending-up', 'color' => '#20c997'],
            ['name' => 'Other Income', 'type' => 'income', 'icon' => 'bx bx-receipt', 'color' => '#6c757d'],
            
            // Expense categories
            ['name' => 'Salaries', 'type' => 'expense', 'icon' => 'bx bx-user', 'color' => '#dc3545'],
            ['name' => 'Rent', 'type' => 'expense', 'icon' => 'bx bx-home', 'color' => '#e83e8c'],
            ['name' => 'Utilities', 'type' => 'expense', 'icon' => 'bx bx-bulb', 'color' => '#fd7e14'],
            ['name' => 'Office Supplies', 'type' => 'expense', 'icon' => 'bx bx-pencil', 'color' => '#ffc107'],
            ['name' => 'Marketing', 'type' => 'expense', 'icon' => 'bx bx-megaphone', 'color' => '#6610f2'],
            ['name' => 'Travel', 'type' => 'expense', 'icon' => 'bx bx-car', 'color' => '#6f42c1'],
            ['name' => 'Other Expenses', 'type' => 'expense', 'icon' => 'bx bx-dots-horizontal-rounded', 'color' => '#343a40'],
        ];
    }
}