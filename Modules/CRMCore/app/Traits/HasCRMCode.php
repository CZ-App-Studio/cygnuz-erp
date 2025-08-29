<?php

namespace Modules\CRMCore\app\Traits;

use Modules\CRMCore\app\Services\CRMCodeGeneratorService;

trait HasCRMCode
{
    /**
     * Boot the HasCRMCode trait
     */
    protected static function bootHasCRMCode(): void
    {
        static::creating(function ($model) {
            $model->generateCodeIfNeeded();
        });
    }

    /**
     * Generate a code if auto-generation is enabled and no code is set
     */
    public function generateCodeIfNeeded(): void
    {
        if (empty($this->code)) {
            $entityType = $this->getCRMEntityType();
            $codeGenerator = app(CRMCodeGeneratorService::class);
            
            if ($codeGenerator->isAutoGenerationEnabled($entityType)) {
                $tenantId = $this->tenant_id ?? null;
                $this->code = $codeGenerator->generateCode($entityType, $tenantId);
            }
        }
    }

    /**
     * Force generate a new code regardless of settings
     */
    public function forceGenerateCode(): string
    {
        $entityType = $this->getCRMEntityType();
        $codeGenerator = app(CRMCodeGeneratorService::class);
        $tenantId = $this->tenant_id ?? null;
        
        $this->code = $codeGenerator->generateCode($entityType, $tenantId);
        
        return $this->code;
    }

    /**
     * Get the entity type for code generation
     * Should be overridden by models if the table name doesn't match
     */
    public function getCRMEntityType(): string
    {
        // Convert table names to entity types
        $tableToEntity = [
            'companies' => 'company',
            'contacts' => 'contact',
            'leads' => 'lead',
            'deals' => 'deal',
            'crm_tasks' => 'task',
            'customers' => 'customer',
        ];

        $tableName = $this->getTable();
        
        return $tableToEntity[$tableName] ?? rtrim($tableName, 's');
    }

    /**
     * Validate the current code format
     */
    public function validateCode(): bool
    {
        if (empty($this->code)) {
            return true; // Allow empty codes
        }

        $entityType = $this->getCRMEntityType();
        $codeGenerator = app(CRMCodeGeneratorService::class);
        
        return $codeGenerator->validateCodeFormat($this->code, $entityType);
    }

    /**
     * Get the next available number for this entity type
     */
    public function getNextAvailableNumber(): int
    {
        $entityType = $this->getCRMEntityType();
        $codeGenerator = app(CRMCodeGeneratorService::class);
        $tenantId = $this->tenant_id ?? null;
        
        return $codeGenerator->getNextNumber($entityType, $tenantId);
    }

    /**
     * Scope to find by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Check if auto-generation is enabled for this entity type
     */
    public function isCodeAutoGenerationEnabled(): bool
    {
        $entityType = $this->getCRMEntityType();
        $codeGenerator = app(CRMCodeGeneratorService::class);
        
        return $codeGenerator->isAutoGenerationEnabled($entityType);
    }
}