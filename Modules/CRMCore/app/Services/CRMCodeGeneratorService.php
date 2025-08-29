<?php

namespace Modules\CRMCore\app\Services;

use App\Services\Settings\ModuleSettingsService;

class CRMCodeGeneratorService
{
    protected ModuleSettingsService $settingsService;

    public function __construct(ModuleSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Generate a unique code for a CRM entity
     *
     * @param string $entity The entity type (company, contact, lead, deal, task, customer)
     * @param string|null $tenantId Optional tenant ID for multi-tenant support
     * @return string|null Returns null if auto-generation is disabled
     */
    public function generateCode(string $entity, ?string $tenantId = null): ?string
    {
        $autoGenerateKey = "auto_generate_{$entity}_codes";
        $prefixKey = "{$entity}_code_prefix";
        $startNumberKey = "{$entity}_code_start_number";

        // Check if auto-generation is enabled
        $autoGenerate = $this->settingsService->get('CRMCore', $autoGenerateKey, false);
        if (!$autoGenerate) {
            return null;
        }

        // Get prefix and starting number from settings
        $prefix = $this->settingsService->get('CRMCore', $prefixKey, strtoupper(substr($entity, 0, 3)));
        $startNumber = (int) $this->settingsService->get('CRMCore', $startNumberKey, 1000);

        return $this->generateUniqueCode($entity, $prefix, $startNumber, $tenantId);
    }

    /**
     * Generate a unique code with the given parameters
     *
     * @param string $entity
     * @param string $prefix
     * @param int $startNumber
     * @param string|null $tenantId
     * @return string
     */
    protected function generateUniqueCode(string $entity, string $prefix, int $startNumber, ?string $tenantId = null): string
    {
        $modelClass = $this->getModelClass($entity);
        
        if (!$modelClass || !class_exists($modelClass)) {
            return $this->buildCode($prefix, $startNumber);
        }

        $query = $modelClass::query();
        
        // Add tenant filter if applicable
        if ($tenantId && method_exists($modelClass, 'where')) {
            $query->where('tenant_id', $tenantId);
        }

        // Find the highest existing code number
        $pattern = $this->buildCodePattern($prefix);
        $lastRecord = $query->where('code', 'like', $pattern)
            ->orderByRaw('CAST(SUBSTRING(code, ' . (strlen($prefix) + 2) . ') AS UNSIGNED) DESC')
            ->first();

        $nextNumber = $startNumber;
        if ($lastRecord && $lastRecord->code) {
            // Extract number from the last code
            $lastNumber = (int) substr($lastRecord->code, strlen($prefix) + 1);
            $nextNumber = max($startNumber, $lastNumber + 1);
        }

        return $this->buildCode($prefix, $nextNumber);
    }

    /**
     * Build a code string
     *
     * @param string $prefix
     * @param int $number
     * @return string
     */
    protected function buildCode(string $prefix, int $number): string
    {
        return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Build a SQL LIKE pattern for finding codes
     *
     * @param string $prefix
     * @return string
     */
    protected function buildCodePattern(string $prefix): string
    {
        return $prefix . '-%';
    }

    /**
     * Get the model class for an entity
     *
     * @param string $entity
     * @return string|null
     */
    protected function getModelClass(string $entity): ?string
    {
        $modelMap = [
            'company' => \Modules\CRMCore\app\Models\Company::class,
            'contact' => \Modules\CRMCore\app\Models\Contact::class,
            'lead' => \Modules\CRMCore\app\Models\Lead::class,
            'deal' => \Modules\CRMCore\app\Models\Deal::class,
            'task' => \Modules\CRMCore\app\Models\Task::class,
            'customer' => \Modules\CRMCore\app\Models\Customer::class,
        ];

        return $modelMap[$entity] ?? null;
    }

    /**
     * Validate a code format
     *
     * @param string $code
     * @param string $entity
     * @return bool
     */
    public function validateCodeFormat(string $code, string $entity): bool
    {
        $prefixKey = "{$entity}_code_prefix";
        $prefix = $this->settingsService->get('CRMCore', $prefixKey, strtoupper(substr($entity, 0, 3)));
        
        // Check if code matches the expected format: PREFIX-NNNN
        return preg_match('/^' . preg_quote($prefix, '/') . '-\d{4,}$/', $code);
    }

    /**
     * Get the next available number for a specific entity type
     *
     * @param string $entity
     * @param string|null $tenantId
     * @return int
     */
    public function getNextNumber(string $entity, ?string $tenantId = null): int
    {
        $startNumberKey = "{$entity}_code_start_number";
        $startNumber = (int) $this->settingsService->get('CRMCore', $startNumberKey, 1000);
        
        $modelClass = $this->getModelClass($entity);
        if (!$modelClass || !class_exists($modelClass)) {
            return $startNumber;
        }

        $prefixKey = "{$entity}_code_prefix";
        $prefix = $this->settingsService->get('CRMCore', $prefixKey, strtoupper(substr($entity, 0, 3)));

        $query = $modelClass::query();
        
        if ($tenantId && method_exists($modelClass, 'where')) {
            $query->where('tenant_id', $tenantId);
        }

        $pattern = $this->buildCodePattern($prefix);
        $lastRecord = $query->where('code', 'like', $pattern)
            ->orderByRaw('CAST(SUBSTRING(code, ' . (strlen($prefix) + 2) . ') AS UNSIGNED) DESC')
            ->first();

        if ($lastRecord && $lastRecord->code) {
            $lastNumber = (int) substr($lastRecord->code, strlen($prefix) + 1);
            return max($startNumber, $lastNumber + 1);
        }

        return $startNumber;
    }

    /**
     * Check if auto-generation is enabled for an entity
     *
     * @param string $entity
     * @return bool
     */
    public function isAutoGenerationEnabled(string $entity): bool
    {
        $autoGenerateKey = "auto_generate_{$entity}_codes";
        return $this->settingsService->get('CRMCore', $autoGenerateKey, false);
    }

    /**
     * Get all supported entities
     *
     * @return array
     */
    public function getSupportedEntities(): array
    {
        return ['company', 'contact', 'lead', 'deal', 'task', 'customer'];
    }
}