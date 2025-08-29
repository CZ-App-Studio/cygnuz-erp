<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingMetadata extends Model
{
    protected $table = 'settings_metadata';
    
    protected $fillable = [
        'category',
        'key',
        'label',
        'type',
        'input_type',
        'options',
        'validation_rules',
        'help_text',
        'sort_order',
        'is_required',
        'default_value',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
    ];

    /**
     * Scope for category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get metadata by category ordered by sort_order
     */
    public static function getByCategory(string $category)
    {
        return static::category($category)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get validation rules as array
     */
    public function getValidationRulesArray(): array
    {
        $rules = $this->validation_rules ?? [];
        
        if ($this->is_required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }
        
        return $rules;
    }

    /**
     * Get validation rules as string
     */
    public function getValidationRulesString(): string
    {
        return implode('|', $this->getValidationRulesArray());
    }
}