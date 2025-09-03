<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

class OrderedMigrationCommand extends Command
{
    protected $signature = 'migrate:erp 
                            {--seed : Run seeders after migrations}
                            {--seed-demo : Run demo seeders for full demo setup}
                            {--seed-production : Run production seeders for minimal setup}';

    protected $description = 'Run migrations in proper dependency order: Base → SystemCore → Core modules → Dependent modules';

    private array $moduleOrder = [];

    private array $coreModules = [];

    private array $dependentModules = [];

    public function handle()
    {
        $this->info('🚀 Starting Ordered Migration Process');
        $this->info('📋 Analyzing module dependencies...');

        // Step 1: Analyze all modules and their dependencies
        $this->analyzeModules();

        // Step 2: Create proper migration order
        $this->createMigrationOrder();

        // Step 3: Display the order
        $this->displayMigrationOrder();

        // Step 4: Confirm before proceeding
        if (! $this->confirm('Proceed with migrations in this order?')) {
            $this->warn('Migration cancelled.');

            return 1;
        }

        // Step 5: Run migrations in order
        $this->runOrderedMigrations();

        // Step 6: Run seeders if requested
        if ($this->option('seed')) {
            $this->runOrderedSeeders();
        } elseif ($this->option('seed-demo')) {
            $this->info("\n🌱 Running demo setup...\n");
            $this->call('erp:demo-setup', ['--skip-permissions' => false]);
        } elseif ($this->option('seed-production')) {
            $this->info("\n🌱 Running production setup...\n");
            $this->call('erp:production-setup', ['--skip-permissions' => false]);
        }

        $this->info('✅ Ordered migration process completed successfully!');

        return 0;
    }

    private function analyzeModules(): void
    {
        $modulesPath = base_path('Modules');
        $modules = [];

        if (! File::exists($modulesPath)) {
            $this->error('Modules directory not found!');

            return;
        }

        // Get all module directories
        $moduleDirs = File::directories($modulesPath);

        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);

            // Check if module is enabled
            $module = Module::find($moduleName);
            if ($module && ! $module->isEnabled()) {
                $this->warn("⏩ Skipping disabled module: {$moduleName}");

                continue;
            }

            $moduleJsonPath = $moduleDir.'/module.json';

            if (File::exists($moduleJsonPath)) {
                $moduleConfig = json_decode(File::get($moduleJsonPath), true);
                $modules[$moduleName] = [
                    'name' => $moduleName,
                    'displayName' => $moduleConfig['displayName'] ?? $moduleName,
                    'isCoreModule' => $moduleConfig['isCoreModule'] ?? false,
                    'priority' => $moduleConfig['priority'] ?? 999,
                    'dependencies' => $moduleConfig['dependencies'] ?? [],
                    'category' => $moduleConfig['category'] ?? 'Other',
                ];
            }
        }

        // Separate core and dependent modules
        foreach ($modules as $module) {
            if ($module['isCoreModule']) {
                $this->coreModules[$module['name']] = $module;
            } else {
                $this->dependentModules[$module['name']] = $module;
            }
        }

        $this->info('📊 Found '.count($this->coreModules).' core modules and '.count($this->dependentModules).' dependent modules');
    }

    private function createMigrationOrder(): void
    {
        $this->moduleOrder = [];

        // 1. Base Laravel migrations first
        $this->moduleOrder[] = [
            'type' => 'base',
            'name' => 'Base Laravel',
            'description' => 'Core Laravel framework tables',
        ];

        // 2. CRMCore first (has contacts table needed by SystemCore)
        if (isset($this->coreModules['CRMCore'])) {
            $this->moduleOrder[] = [
                'type' => 'core',
                'name' => 'CRMCore',
                'description' => 'CRM Core (contacts, companies - required by SystemCore)',
            ];
            unset($this->coreModules['CRMCore']);
        }

        // 3. SystemCore second (depends on contacts from CRMCore)
        if (isset($this->coreModules['SystemCore'])) {
            $this->moduleOrder[] = [
                'type' => 'core',
                'name' => 'SystemCore',
                'description' => 'System Core (customers, base tables - depends on CRMCore)',
            ];
            unset($this->coreModules['SystemCore']);
        }

        // 4. Other core modules ordered by priority
        $orderedCoreModules = $this->coreModules;
        uasort($orderedCoreModules, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        foreach ($orderedCoreModules as $module) {
            $this->moduleOrder[] = [
                'type' => 'core',
                'name' => $module['name'],
                'description' => $module['displayName'].' (Core Module)',
            ];
        }

        // 5. Dependent modules with dependency resolution
        $this->resolveDependentModules();
    }

    private function resolveDependentModules(): void
    {
        $remaining = $this->dependentModules;
        $resolved = array_keys($this->coreModules);
        $resolved[] = 'SystemCore'; // Already added

        $maxIterations = 50; // Prevent infinite loops
        $iteration = 0;

        while (! empty($remaining) && $iteration < $maxIterations) {
            $iteration++;
            $progressMade = false;

            foreach ($remaining as $moduleName => $module) {
                // Check if all dependencies are resolved
                $canResolve = true;
                foreach ($module['dependencies'] as $dependency) {
                    if (! in_array($dependency, $resolved)) {
                        $canResolve = false;
                        break;
                    }
                }

                if ($canResolve) {
                    $this->moduleOrder[] = [
                        'type' => 'dependent',
                        'name' => $moduleName,
                        'description' => $module['displayName'].' (depends on: '.implode(', ', $module['dependencies']).')',
                    ];
                    $resolved[] = $moduleName;
                    unset($remaining[$moduleName]);
                    $progressMade = true;
                }
            }

            if (! $progressMade) {
                // Add remaining modules without dependency check
                foreach ($remaining as $moduleName => $module) {
                    $this->moduleOrder[] = [
                        'type' => 'dependent',
                        'name' => $moduleName,
                        'description' => $module['displayName'].' (circular/missing deps: '.implode(', ', $module['dependencies']).')',
                    ];
                }
                break;
            }
        }
    }

    private function displayMigrationOrder(): void
    {
        $this->info("\n📋 Migration Order:");
        $this->info('═══════════════════════════════════════════════════════════════');

        foreach ($this->moduleOrder as $index => $item) {
            $number = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $icon = match ($item['type']) {
                'base' => '🏗️',
                'core' => '🔧',
                'dependent' => '📦',
                default => '❓'
            };

            $this->line("$number. $icon {$item['name']} - {$item['description']}");
        }

        $this->info("═══════════════════════════════════════════════════════════════\n");
    }

    private function runOrderedMigrations(): void
    {
        $this->info("\n🔄 Running migrations in dependency order...\n");

        foreach ($this->moduleOrder as $index => $item) {
            $number = $index + 1;
            $this->info("[$number/".count($this->moduleOrder)."] Migrating: {$item['name']}");

            try {
                if ($item['type'] === 'base') {
                    // Run base Laravel migrations
                    $this->runBaseMigrations();
                } else {
                    // Run module migrations
                    $this->runModuleMigration($item['name']);
                }

                $this->info("✅ {$item['name']} migrations completed\n");

            } catch (\Exception $e) {
                $this->error("❌ Failed to migrate {$item['name']}: ".$e->getMessage());
                $this->warn("⚠️  Continuing with next module...\n");
            }
        }
    }

    private function runBaseMigrations(): void
    {
        // Run only the base Laravel migrations (not module migrations)
        Artisan::call('migrate', [
            '--path' => 'database/migrations',
            '--force' => true,
        ]);
    }

    private function runModuleMigration(string $moduleName): void
    {
        // Check if module is enabled before migrating
        $module = Module::find($moduleName);
        if ($module && ! $module->isEnabled()) {
            $this->warn("⏩ Module $moduleName is disabled, skipping migration...");

            return;
        }

        $modulePath = base_path("Modules/$moduleName");

        if (! File::exists($modulePath)) {
            $this->warn("Module $moduleName not found, skipping...");

            return;
        }

        // Check if module has migrations
        $migrationsPath = "$modulePath/database/migrations";
        if (! File::exists($migrationsPath) || empty(File::files($migrationsPath))) {
            $this->comment("No migrations found for $moduleName, skipping...");

            return;
        }

        // Run module migrations
        Artisan::call('module:migrate', [
            'module' => $moduleName,
            '--force' => true,
        ]);
    }

    private function runOrderedSeeders(): void
    {
        $this->info("\n🌱 Running seeders in dependency order...\n");

        foreach ($this->moduleOrder as $index => $item) {
            if ($item['type'] === 'base') {
                continue; // Skip base Laravel for seeders
            }

            $number = $index + 1;
            $this->info("[$number/".count($this->moduleOrder)."] Seeding: {$item['name']}");

            try {
                $this->runModuleSeeder($item['name']);
                $this->info("✅ {$item['name']} seeding completed\n");

            } catch (\Exception $e) {
                $this->error("❌ Failed to seed {$item['name']}: ".$e->getMessage());
                $this->warn("⚠️  Continuing with next module...\n");
            }
        }
    }

    private function runModuleSeeder(string $moduleName): void
    {
        // Check if module is enabled before seeding
        $module = Module::find($moduleName);
        if ($module && ! $module->isEnabled()) {
            $this->warn("⏩ Module $moduleName is disabled, skipping seeder...");

            return;
        }

        $modulePath = base_path("Modules/$moduleName");

        if (! File::exists($modulePath)) {
            $this->warn("Module $moduleName not found, skipping seeder...");

            return;
        }

        // Check if module has seeders
        $seedersPath = "$modulePath/database/seeders";
        if (! File::exists($seedersPath)) {
            $this->comment("No seeders found for $moduleName, skipping...");

            return;
        }

        // Run module seeders
        try {
            Artisan::call('module:seed', [
                'module' => $moduleName,
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            $this->comment("Seeder not available or failed for $moduleName: ".$e->getMessage());
        }
    }
}
