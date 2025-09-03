<?php

namespace Modules\AICore\Services;

use Illuminate\Support\Facades\File;
use Modules\AICore\Models\AIModuleConfiguration;
use Nwidart\Modules\Facades\Module;

class AIModuleDetectionService
{
    /**
     * Known AI-enabled modules and their configurations
     */
    protected array $knownAIModules = [
        'AIChat' => [
            'display_name' => 'AI Chat Assistant',
            'description' => 'Interactive AI chat interface for user queries and assistance',
            'max_tokens' => 4096,
            'temperature' => 0.7,
        ],
        'DocumentSummarizerAI' => [
            'display_name' => 'Document Summarizer',
            'description' => 'AI-powered document summarization and analysis',
            'max_tokens' => 2048,
            'temperature' => 0.5,
        ],
        'AutoDescriptionAI' => [
            'display_name' => 'Auto Description Generator',
            'description' => 'Automatically generate descriptions for products, services, and content',
            'max_tokens' => 1024,
            'temperature' => 0.8,
        ],
        'HRAssistantAI' => [
            'display_name' => 'HR Assistant',
            'description' => 'AI assistant for HR tasks, employee queries, and policy information',
            'max_tokens' => 2048,
            'temperature' => 0.6,
        ],
        'PDFAnalyzerAI' => [
            'display_name' => 'PDF Analyzer',
            'description' => 'Extract and analyze data from PDF documents',
            'max_tokens' => 4096,
            'temperature' => 0.3,
        ],
        'EmailAssistantAI' => [
            'display_name' => 'Email Assistant',
            'description' => 'AI-powered email composition and response generation',
            'max_tokens' => 1024,
            'temperature' => 0.7,
        ],
        'CodeAssistantAI' => [
            'display_name' => 'Code Assistant',
            'description' => 'AI helper for code generation and debugging',
            'max_tokens' => 8192,
            'temperature' => 0.3,
        ],
        'TranslatorAI' => [
            'display_name' => 'AI Translator',
            'description' => 'Multi-language translation powered by AI',
            'max_tokens' => 2048,
            'temperature' => 0.3,
        ],
    ];

    /**
     * Detect all AI-enabled modules in the system
     */
    public function detectAIModules(): array
    {
        $aiModules = [];

        // Get all enabled modules
        $modules = Module::allEnabled();

        foreach ($modules as $module) {
            if ($this->isAIModule($module)) {
                $moduleName = $module->getName();
                $moduleData = $this->getModuleData($module);

                $aiModules[$moduleName] = [
                    'name' => $moduleName,
                    'display_name' => $moduleData['display_name'],
                    'description' => $moduleData['description'],
                    'path' => $module->getPath(),
                    'enabled' => $module->isEnabled(),
                    'has_aicore_dependency' => $this->hasAICoreDependency($module),
                    'default_config' => $moduleData,
                ];
            }
        }

        return $aiModules;
    }

    /**
     * Check if a module is AI-enabled
     */
    protected function isAIModule($module): bool
    {
        $moduleName = $module->getName();

        // Exclude provider modules - these provide AI services, not consume them
        $excludedModules = [
            'AICore',  // Core module itself
            'AzureOpenAIProvider',
            'ClaudeAIProvider',
            'GeminiAIProvider',
            'OpenAIProvider',
            'AnthropicProvider',
            'GoogleAIProvider',
        ];

        if (in_array($moduleName, $excludedModules)) {
            return false;
        }

        // Check if it's a known AI module
        if (isset($this->knownAIModules[$moduleName])) {
            return true;
        }

        // Check if module name contains AI-related keywords (but not Provider)
        if (! str_contains($moduleName, 'Provider')) {
            $aiKeywords = ['AI', 'Ai', 'GPT', 'Gpt', 'ML', 'Intelligence', 'Assistant', 'Analyzer', 'Summarizer'];
            foreach ($aiKeywords as $keyword) {
                if (str_contains($moduleName, $keyword)) {
                    return true;
                }
            }
        }

        // Check if module has AICore as dependency
        if ($this->hasAICoreDependency($module)) {
            return true;
        }

        // Check if module uses AI services
        return $this->usesAIServices($module);
    }

    /**
     * Check if module has AICore as a dependency
     */
    protected function hasAICoreDependency($module): bool
    {
        $moduleJson = $module->getPath().'/module.json';

        if (File::exists($moduleJson)) {
            $config = json_decode(File::get($moduleJson), true);
            $dependencies = $config['dependencies'] ?? [];

            return in_array('AICore', $dependencies);
        }

        return false;
    }

    /**
     * Check if module uses AI services in its code
     */
    protected function usesAIServices($module): bool
    {
        $controllersPath = $module->getPath().'/Http/Controllers';
        $servicesPath = $module->getPath().'/Services';

        $aiServiceClasses = [
            'AIRequestService',
            'AIUsageTracker',
            'AIProviderService',
            'GeminiProviderService',
            'OpenAIService',
            'ClaudeService',
        ];

        // Check controllers
        if (File::exists($controllersPath)) {
            $files = File::allFiles($controllersPath);
            foreach ($files as $file) {
                $content = File::get($file->getPathname());
                foreach ($aiServiceClasses as $class) {
                    if (str_contains($content, $class)) {
                        return true;
                    }
                }
            }
        }

        // Check services
        if (File::exists($servicesPath)) {
            $files = File::allFiles($servicesPath);
            foreach ($files as $file) {
                $content = File::get($file->getPathname());
                foreach ($aiServiceClasses as $class) {
                    if (str_contains($content, $class)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get module data with defaults
     */
    protected function getModuleData($module): array
    {
        $moduleName = $module->getName();

        // Use known configuration if available
        if (isset($this->knownAIModules[$moduleName])) {
            return $this->knownAIModules[$moduleName];
        }

        // Generate default configuration
        $moduleJson = $module->getPath().'/module.json';
        $displayName = $moduleName;
        $description = '';

        if (File::exists($moduleJson)) {
            $config = json_decode(File::get($moduleJson), true);
            $displayName = $config['displayName'] ?? $config['name'] ?? $moduleName;
            $description = $config['description'] ?? '';
        }

        return [
            'display_name' => $displayName,
            'description' => $description,
            'max_tokens' => 2048,
            'temperature' => 0.7,
        ];
    }

    /**
     * Sync detected modules to database
     */
    public function syncModulesToDatabase(): array
    {
        $detectedModules = $this->detectAIModules();
        $synced = [];

        foreach ($detectedModules as $moduleName => $moduleData) {
            $config = AIModuleConfiguration::firstOrNew(['module_name' => $moduleName]);

            // Only update if it's a new record
            if (! $config->exists) {
                $config->fill([
                    'module_display_name' => $moduleData['display_name'],
                    'module_description' => $moduleData['description'],
                    'max_tokens_limit' => $moduleData['default_config']['max_tokens'] ?? 2048,
                    'temperature_default' => $moduleData['default_config']['temperature'] ?? 0.7,
                    'is_active' => $moduleData['enabled'],
                    'priority' => $this->getModulePriority($moduleName),
                ]);

                $config->save();
                $synced[] = $moduleName;
            }
        }

        return $synced;
    }

    /**
     * Get module priority for ordering
     */
    protected function getModulePriority(string $moduleName): int
    {
        $priorities = [
            'AIChat' => 1,
            'DocumentSummarizerAI' => 2,
            'AutoDescriptionAI' => 3,
            'HRAssistantAI' => 4,
            'PDFAnalyzerAI' => 5,
            'EmailAssistantAI' => 6,
            'CodeAssistantAI' => 7,
            'TranslatorAI' => 8,
        ];

        return $priorities[$moduleName] ?? 99;
    }

    /**
     * Get module configuration or create default
     */
    public function getModuleConfiguration(string $moduleName): ?AIModuleConfiguration
    {
        $config = AIModuleConfiguration::where('module_name', $moduleName)->first();

        if (! $config) {
            // Try to detect and create configuration
            $detectedModules = $this->detectAIModules();

            if (isset($detectedModules[$moduleName])) {
                $moduleData = $detectedModules[$moduleName];

                $config = AIModuleConfiguration::create([
                    'module_name' => $moduleName,
                    'module_display_name' => $moduleData['display_name'],
                    'module_description' => $moduleData['description'],
                    'max_tokens_limit' => $moduleData['default_config']['max_tokens'] ?? 2048,
                    'temperature_default' => $moduleData['default_config']['temperature'] ?? 0.7,
                    'is_active' => $moduleData['enabled'],
                    'priority' => $this->getModulePriority($moduleName),
                ]);
            }
        }

        return $config;
    }
}
