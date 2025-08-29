<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ERPPermissionSeeder;
use Database\Seeders\ERPRoleSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Database\Seeders\DemoSeeder;
use Modules\HRCore\database\seeders\HRCoreDemoDataSeeder;
use Modules\HRCore\database\seeders\LeaveTypeSeeder;
use Modules\HRCore\database\seeders\ExpenseTypeSeeder;
use Modules\HRCore\database\seeders\HRCoreSettingsSeeder;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

class DemoSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:demo-setup
                            {--skip-permissions : Skip permission seeding}
                            {--skip-modules : Skip module demo data}
                            {--with-migrations : Run migrations first using migrate:erp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up demo environment with all demo data and users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===========================================');
        $this->info('   ERP Demo Environment Setup');
        $this->info('===========================================');
        $this->newLine();

        // Step 0: Run migrations if requested
        if ($this->option('with-migrations')) {
            $this->info('Step 0: Running database migrations...');
            $this->call('migrate:erp');
            $this->info('✓ Migrations completed');
            $this->newLine();
        }

        // Step 1: Seed permissions (base + module permissions)
        if (!$this->option('skip-permissions')) {
            $this->info('Step 1: Seeding permissions and roles...');

            // Base permissions
            $this->call('db:seed', ['--class' => ERPPermissionSeeder::class]);

            // Module permissions following the same order as migrate:erp
            $this->seedModulePermissions();

            // Create roles after all permissions are available
            $this->call('db:seed', ['--class' => ERPRoleSeeder::class]);

            $this->info('✓ Permissions and roles created');
        } else {
            $this->info('Step 1: Skipping permissions (--skip-permissions flag)');
        }

        $this->newLine();

        // Step 2: Seed system settings
        $this->info('Step 2: Seeding system settings...');
        $this->call('db:seed', ['--class' => SystemSettingsSeeder::class]);
        $this->info('✓ System settings created');

        $this->newLine();

        // Step 3: Seed HRCore base data
        $this->info('Step 3: Seeding HRCore base data...');
        $this->call('db:seed', ['--class' => LeaveTypeSeeder::class]);
        $this->call('db:seed', ['--class' => ExpenseTypeSeeder::class]);
        $this->call('db:seed', ['--class' => HRCoreSettingsSeeder::class]);
        $this->info('✓ HRCore base data created');

        $this->newLine();

        // Step 4: Seed HRCore demo data
        $this->info('Step 4: Seeding HRCore demo data...');
        $this->call('db:seed', ['--class' => HRCoreDemoDataSeeder::class]);
        $this->info('✓ HRCore demo data created');

        $this->newLine();

        // Step 5: Create demo users
        $this->info('Step 5: Creating demo user accounts...');
        $this->call('db:seed', ['--class' => DemoSeeder::class]);
        $this->info('✓ Demo users created');

        $this->newLine();

        // Step 6: Seed module demo data (following migrate:erp order)
        if (!$this->option('skip-modules')) {
            $this->info('Step 6: Seeding module demo data...');
            $this->seedModuleDemoData();
            $this->info('✓ Module demo data completed');
        } else {
            $this->info('Step 6: Skipping module demo data (--skip-modules flag)');
        }

        $this->newLine();
        $this->info('===========================================');
        $this->info('   Demo Setup Complete!');
        $this->info('===========================================');
        $this->newLine();

        // Display demo accounts
        $this->displayDemoAccounts();

        $this->newLine();
        $this->info('All demo accounts use password: 123456');
        $this->newLine();

        return 0;
    }

    /**
     * Seed module permissions in the same order as migrate:erp
     */
    private function seedModulePermissions(): void
    {
        $modulePermissionSeeders = [
            // Core modules first (following migrate:erp order)
            '\Modules\CRMCore\Database\Seeders\CRMCorePermissionSeeder',
            '\Modules\SystemCore\Database\Seeders\SystemCorePermissionSeeder',
            '\Modules\HRCore\Database\Seeders\HRCorePermissionSeeder',
            '\Modules\AccountingCore\Database\Seeders\AccountingCorePermissionSeeder',
            '\Modules\PMCore\Database\Seeders\PMCorePermissionSeeder',
            '\Modules\WMSInventoryCore\Database\Seeders\WMSInventoryCorePermissionSeeder',
            '\Modules\FileManagerCore\Database\Seeders\FileManagerCorePermissionSeeder',
            '\Modules\AICore\Database\Seeders\AICorePermissionSeeder',
        ];

        foreach ($modulePermissionSeeders as $seederClass) {
            if (class_exists($seederClass)) {
                $moduleName = $this->extractModuleName($seederClass);

                // Check if module is enabled
                $module = Module::find($moduleName);
                if ($module && !$module->isEnabled()) {
                    $this->warn("  ⏩ Skipping disabled module: {$moduleName}");
                    continue;
                }

                $this->info("  - Seeding {$moduleName} permissions...");
                try {
                    $this->call('db:seed', ['--class' => $seederClass]);
                } catch (\Exception $e) {
                    $this->warn("    ⚠ Could not seed {$moduleName} permissions: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Seed module demo data following the module priority order
     */
    private function seedModuleDemoData(): void
    {
        // Get module order based on priority
        $moduleOrder = $this->getModuleOrder();

        foreach ($moduleOrder as $moduleName) {
            $this->seedModuleDemo($moduleName);
        }
    }

    /**
     * Get modules in priority order (same as migrate:erp)
     */
    private function getModuleOrder(): array
    {
        $modules = [];
        $modulesPath = base_path('Modules');

        if (!File::exists($modulesPath)) {
            return [];
        }

        $moduleDirs = File::directories($modulesPath);

        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);

            // Check if module is enabled
            $module = Module::find($moduleName);
            if ($module && !$module->isEnabled()) {
                continue; // Skip disabled modules
            }

            $moduleJsonPath = $moduleDir . '/module.json';

            if (File::exists($moduleJsonPath)) {
                $moduleConfig = json_decode(File::get($moduleJsonPath), true);
                $modules[$moduleName] = [
                    'name' => $moduleName,
                    'isCoreModule' => $moduleConfig['isCoreModule'] ?? false,
                    'priority' => $moduleConfig['priority'] ?? 999,
                    'dependencies' => $moduleConfig['dependencies'] ?? [],
                ];
            }
        }

        // Sort modules by priority and dependencies
        $ordered = [];

        // CRMCore first (if exists)
        if (isset($modules['CRMCore'])) {
            $ordered[] = 'CRMCore';
            unset($modules['CRMCore']);
        }

        // SystemCore second (if exists)
        if (isset($modules['SystemCore'])) {
            $ordered[] = 'SystemCore';
            unset($modules['SystemCore']);
        }

        // HRCore already seeded separately, skip it
        unset($modules['HRCore']);

        // Sort remaining by priority
        uasort($modules, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        foreach ($modules as $module) {
            $ordered[] = $module['name'];
        }

        return $ordered;
    }

    /**
     * Seed a specific module's demo data
     */
    private function seedModuleDemo(string $moduleName): void
    {
        // Check if module is enabled
        $module = Module::find($moduleName);
        if ($module && !$module->isEnabled()) {
            $this->warn("  ⏩ Skipping disabled module: {$moduleName}");
            return;
        }

        $seederClass = "Modules\\{$moduleName}\\database\\seeders\\{$moduleName}DatabaseSeeder";

        // Try alternative naming patterns
        if (!class_exists($seederClass)) {
            $seederClass = "Modules\\{$moduleName}\\Database\\Seeders\\{$moduleName}DatabaseSeeder";
        }
        if (!class_exists($seederClass)) {
            $seederClass = "Modules\\{$moduleName}\\database\\seeders\\{$moduleName}Seeder";
        }
        if (!class_exists($seederClass)) {
            $seederClass = "Modules\\{$moduleName}\\Database\\Seeders\\{$moduleName}Seeder";
        }

        if (class_exists($seederClass)) {
            $this->info("  - Seeding {$moduleName} demo data...");
            try {
                $this->call('module:seed', ['module' => $moduleName]);
            } catch (\Exception $e) {
                $this->warn("    ⚠ Could not seed {$moduleName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Display demo accounts table
     */
    private function displayDemoAccounts(): void
    {
        $this->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 'superadmin@demo.com', '123456'],
                ['Admin', 'admin@demo.com', '123456'],
                ['HR Manager', 'hr.manager@demo.com', '123456'],
                ['HR Executive', 'hr.executive@demo.com', '123456'],
                ['Accounting Manager', 'accounting.manager@demo.com', '123456'],
                ['Accounting Executive', 'accounting.executive@demo.com', '123456'],
                ['CRM Manager', 'crm.manager@demo.com', '123456'],
                ['Project Manager', 'project.manager@demo.com', '123456'],
                ['Inventory Manager', 'inventory.manager@demo.com', '123456'],
                ['Sales Manager', 'sales.manager@demo.com', '123456'],
                ['Sales Executive', 'sales.executive@demo.com', '123456'],
                ['Team Leader', 'team.leader@demo.com', '123456'],
                ['Employee', 'employee@demo.com', '123456'],
                ['Field Employee', 'field.employee@demo.com', '123456'],
                ['Client', 'client@demo.com', '123456'],
            ]
        );
    }

    /**
     * Extract module name from seeder class path
     */
    private function extractModuleName(string $seederClass): string
    {
        if (preg_match('/Modules\\\\(.+?)\\\\/', $seederClass, $matches)) {
            return $matches[1];
        }
        return 'Unknown';
    }
}
