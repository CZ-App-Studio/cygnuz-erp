<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

class ModuleToggleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:toggle 
                            {action : Action to perform: list, enable-non-core, disable-non-core, enable-all, disable-all, backup, restore}
                            {--dry-run : Show what would be changed without making changes}
                            {--force : Skip confirmation prompts}
                            {--exclude=* : Modules to exclude from the operation}
                            {--include=* : Specific modules to include (overrides non-core filter)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable or disable non-core modules for testing purposes';

    /**
     * Path to modules status file
     */
    private string $statusFile;

    /**
     * Path to backup file
     */
    private string $backupFile;

    /**
     * Initialize paths
     */
    public function __construct()
    {
        parent::__construct();
        $this->statusFile = base_path('modules_statuses.json');
        $this->backupFile = base_path('modules_statuses.backup.json');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        $this->info('===========================================');
        $this->info('   Module Toggle Utility');
        $this->info('===========================================');
        $this->newLine();

        switch ($action) {
            case 'list':
                $this->listModules();
                break;
            
            case 'enable-non-core':
                $this->toggleNonCoreModules(true);
                break;
            
            case 'disable-non-core':
                $this->toggleNonCoreModules(false);
                break;
            
            case 'enable-all':
                $this->toggleAllModules(true);
                break;
            
            case 'disable-all':
                $this->toggleAllModules(false);
                break;
            
            case 'backup':
                $this->backupStatus();
                break;
            
            case 'restore':
                $this->restoreStatus();
                break;
            
            default:
                $this->error("Invalid action: {$action}");
                $this->info('Valid actions: list, enable-non-core, disable-non-core, enable-all, disable-all, backup, restore');
                return 1;
        }

        return 0;
    }

    /**
     * List all modules with their status
     */
    private function listModules(): void
    {
        $modules = $this->getModulesWithStatus();
        
        $this->info('Module Status Overview:');
        $this->newLine();

        // Separate core and non-core modules
        $coreModules = [];
        $nonCoreModules = [];
        $stats = [
            'total' => 0,
            'enabled' => 0,
            'disabled' => 0,
            'core' => 0,
            'non_core' => 0
        ];

        foreach ($modules as $module) {
            $stats['total']++;
            
            if ($module['enabled']) {
                $stats['enabled']++;
            } else {
                $stats['disabled']++;
            }

            if ($module['is_core']) {
                $coreModules[] = $module;
                $stats['core']++;
            } else {
                $nonCoreModules[] = $module;
                $stats['non_core']++;
            }
        }

        // Display Core Modules
        $this->info('🔧 Core Modules:');
        $this->table(
            ['Module', 'Display Name', 'Status', 'Priority', 'Dependencies'],
            array_map(function($m) {
                return [
                    $m['name'],
                    $m['display_name'],
                    $m['enabled'] ? '✅ Enabled' : '❌ Disabled',
                    $m['priority'],
                    implode(', ', $m['dependencies'])
                ];
            }, $coreModules)
        );

        // Display Non-Core Modules
        $this->info('📦 Non-Core Modules:');
        $this->table(
            ['Module', 'Display Name', 'Status', 'Category', 'Dependencies'],
            array_map(function($m) {
                return [
                    $m['name'],
                    $m['display_name'],
                    $m['enabled'] ? '✅ Enabled' : '❌ Disabled',
                    $m['category'],
                    implode(', ', $m['dependencies'])
                ];
            }, $nonCoreModules)
        );

        // Display Statistics
        $this->info('📊 Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Modules', $stats['total']],
                ['Core Modules', $stats['core']],
                ['Non-Core Modules', $stats['non_core']],
                ['Enabled', $stats['enabled']],
                ['Disabled', $stats['disabled']],
            ]
        );
    }

    /**
     * Toggle non-core modules
     */
    private function toggleNonCoreModules(bool $enable): void
    {
        $action = $enable ? 'enable' : 'disable';
        $modules = $this->getModulesWithStatus();
        $excludedModules = $this->option('exclude') ?: [];
        $includedModules = $this->option('include') ?: [];
        
        // Filter modules to toggle
        $modulesToToggle = [];
        foreach ($modules as $module) {
            // Skip if in exclude list
            if (in_array($module['name'], $excludedModules)) {
                continue;
            }

            // If include list is specified, only process those
            if (!empty($includedModules)) {
                if (in_array($module['name'], $includedModules)) {
                    $modulesToToggle[] = $module;
                }
            } else {
                // Otherwise, process all non-core modules
                if (!$module['is_core']) {
                    $modulesToToggle[] = $module;
                }
            }
        }

        if (empty($modulesToToggle)) {
            $this->warn('No modules to toggle based on your criteria.');
            return;
        }

        // Display modules that will be toggled
        $this->info("Modules to {$action}:");
        $this->table(
            ['Module', 'Display Name', 'Current Status'],
            array_map(function($m) {
                return [
                    $m['name'],
                    $m['display_name'],
                    $m['enabled'] ? 'Enabled' : 'Disabled'
                ];
            }, $modulesToToggle)
        );

        // Dry run check
        if ($this->option('dry-run')) {
            $this->info('🔍 Dry run mode - no changes made.');
            return;
        }

        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm("Are you sure you want to {$action} " . count($modulesToToggle) . " modules?")) {
                $this->warn('Operation cancelled.');
                return;
            }
        }

        // Create backup before making changes
        $this->backupStatus(true);

        // Toggle modules
        $succeeded = 0;
        $failed = 0;

        foreach ($modulesToToggle as $module) {
            try {
                if ($enable) {
                    Module::enable($module['name']);
                } else {
                    Module::disable($module['name']);
                }
                $succeeded++;
                $this->info("✓ {$module['name']} - {$action}d");
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ {$module['name']} - Failed: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Operation complete: {$succeeded} succeeded, {$failed} failed");

        // Clear caches
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->info('✓ Caches cleared');
    }

    /**
     * Toggle all modules (dangerous operation)
     */
    private function toggleAllModules(bool $enable): void
    {
        $action = $enable ? 'enable' : 'disable';
        
        $this->warn("⚠️  WARNING: This will {$action} ALL modules including core modules!");
        $this->warn("This operation is typically used for debugging purposes only.");
        
        if (!$this->option('force')) {
            if (!$this->confirm("Are you absolutely sure you want to {$action} ALL modules?")) {
                $this->warn('Operation cancelled.');
                return;
            }
        }

        // Create backup
        $this->backupStatus(true);

        $modules = Module::all();
        $succeeded = 0;
        $failed = 0;

        foreach ($modules as $module) {
            try {
                if ($enable) {
                    Module::enable($module->getName());
                } else {
                    Module::disable($module->getName());
                }
                $succeeded++;
                $this->info("✓ {$module->getName()} - {$action}d");
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ {$module->getName()} - Failed: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Operation complete: {$succeeded} succeeded, {$failed} failed");

        // Clear caches
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->info('✓ Caches cleared');
    }

    /**
     * Backup current module status
     */
    private function backupStatus(bool $silent = false): void
    {
        if (!File::exists($this->statusFile)) {
            if (!$silent) {
                $this->error('Module status file not found!');
            }
            return;
        }

        // Create backups directory if it doesn't exist
        $backupDir = base_path('backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir);
        }

        // Create timestamped backup
        $timestamp = date('Y-m-d_H-i-s');
        $backupPath = $backupDir . '/modules_statuses_' . $timestamp . '.json';
        
        File::copy($this->statusFile, $backupPath);
        
        // Also create a latest backup for quick restore
        File::copy($this->statusFile, $this->backupFile);

        if (!$silent) {
            $this->info('✓ Module status backed up to:');
            $this->info('  - ' . $backupPath);
            $this->info('  - ' . $this->backupFile . ' (latest)');
        }
    }

    /**
     * Restore module status from backup
     */
    private function restoreStatus(): void
    {
        // Check for available backups
        $backupDir = base_path('backups');
        $backups = [];

        if (File::exists($this->backupFile)) {
            $backups[] = [
                'path' => $this->backupFile,
                'name' => 'Latest backup',
                'time' => date('Y-m-d H:i:s', filemtime($this->backupFile))
            ];
        }

        if (File::exists($backupDir)) {
            $files = File::glob($backupDir . '/modules_statuses_*.json');
            foreach ($files as $file) {
                $backups[] = [
                    'path' => $file,
                    'name' => basename($file),
                    'time' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
        }

        if (empty($backups)) {
            $this->error('No backup files found!');
            return;
        }

        // Display available backups
        $this->info('Available backups:');
        foreach ($backups as $index => $backup) {
            $this->line(($index + 1) . ". {$backup['name']} - {$backup['time']}");
        }

        $choice = $this->ask('Select backup to restore (enter number)', 1);
        $selectedIndex = (int)$choice - 1;

        if (!isset($backups[$selectedIndex])) {
            $this->error('Invalid selection!');
            return;
        }

        $selectedBackup = $backups[$selectedIndex];

        if (!$this->option('force')) {
            if (!$this->confirm("Restore from {$selectedBackup['name']}?")) {
                $this->warn('Restore cancelled.');
                return;
            }
        }

        // Backup current status before restoring
        $this->backupStatus(true);

        // Restore the selected backup
        File::copy($selectedBackup['path'], $this->statusFile);

        $this->info('✓ Module status restored from: ' . $selectedBackup['name']);

        // Clear caches
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->info('✓ Caches cleared');
        
        // Show current status
        $this->newLine();
        $this->info('Current module status after restore:');
        $this->listModules();
    }

    /**
     * Get all modules with their status and metadata
     */
    private function getModulesWithStatus(): array
    {
        $modules = [];
        $allModules = Module::all();
        
        foreach ($allModules as $module) {
            $moduleJson = $module->getPath() . '/module.json';
            $config = [];
            
            if (File::exists($moduleJson)) {
                $config = json_decode(File::get($moduleJson), true);
            }

            $modules[] = [
                'name' => $module->getName(),
                'display_name' => $config['displayName'] ?? $module->getName(),
                'enabled' => $module->isEnabled(),
                'is_core' => $config['isCoreModule'] ?? false,
                'priority' => $config['priority'] ?? 999,
                'dependencies' => $config['dependencies'] ?? [],
                'category' => $config['category'] ?? 'Other',
            ];
        }

        // Sort by priority then name
        usort($modules, function($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return strcmp($a['name'], $b['name']);
            }
            return $a['priority'] <=> $b['priority'];
        });

        return $modules;
    }
}