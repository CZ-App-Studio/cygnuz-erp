<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Str;
use Modules\HRCore\app\Models\Designation;
use Modules\HRCore\app\Models\Shift;
use Modules\HRCore\app\Models\Team;
use Modules\HRCore\database\seeders\HRCoreProductionSeeder;
use Database\Seeders\ERPPermissionSeeder;
use Database\Seeders\ERPRoleSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Nwidart\Modules\Facades\Module;

class ProductionSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:production-setup
                            {--email= : Email for super admin account}
                            {--password= : Password for super admin account (default: 123456)}
                            {--skip-permissions : Skip permission seeding}
                            {--with-migrations : Run migrations first using migrate:erp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up production environment with minimal data and super admin account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===========================================');
        $this->info('   ERP Production Environment Setup');
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

        // Step 3: Create HR Core production data
        $this->info('Step 3: Creating default HR data...');
        $this->call('db:seed', ['--class' => HRCoreProductionSeeder::class]);
        $this->info('✓ Default HR data created');

        $this->newLine();

        // Step 4: Create super admin account
        $this->info('Step 4: Creating super admin account...');

        // Get email
        $email = $this->option('email');
        if (!$email) {
            $email = $this->ask('Enter email for super admin account');

            // Validate email
            while (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Invalid email format. Please try again.');
                $email = $this->ask('Enter email for super admin account');
            }
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists!");
            if (!$this->confirm('Do you want to update the existing user to super admin?')) {
                return 1;
            }

            $user = User::where('email', $email)->first();
            $user->syncRoles(['super_admin']);
            $this->info('✓ Existing user updated to super admin');
        } else {
            // Get password
            $password = $this->option('password') ?: '123456';

            // Get required data
            $shift = Shift::where('code', 'SH-001')->first();
            $team = Team::where('code', 'TM-001')->first();
            $designation = Designation::where('code', 'DES-001')->first();

            if (!$shift || !$team || !$designation) {
                $this->error('Default data not found. Please check HRCore production seeder.');
                return 1;
            }

            // Ask for additional details
            $firstName = $this->ask('Enter first name', 'Super');
            $lastName = $this->ask('Enter last name', 'Admin');
            $phone = $this->ask('Enter phone number', '1000000001');

            // Create super admin
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'phone_verified_at' => now(),
                'password' => bcrypt($password),
                'code' => 'EMP-001',
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'shift_id' => $shift->id,
                'team_id' => $team->id,
                'designation_id' => $designation->id,
                'gender' => 'male',
                'date_of_joining' => now(),
            ]);

            $user->assignRole('super_admin');
            $this->info('✓ Super admin account created');
        }

        $this->newLine();

        // Step 5: Seed essential module data
        $this->info('Step 5: Seeding essential module data...');

        $essentialSeeders = [
            ['module' => 'HRCore', 'seeder' => 'LeaveTypeSeeder'],
            ['module' => 'HRCore', 'seeder' => 'ExpenseTypeSeeder'],
            ['module' => 'HRCore', 'seeder' => 'HRCoreSettingsSeeder'],
        ];

        foreach ($essentialSeeders as $item) {
            // Check if module is enabled
            $module = Module::find($item['module']);
            if ($module && !$module->isEnabled()) {
                $this->warn("  ⏩ Skipping disabled module: {$item['module']}");
                continue;
            }

            $seederClass = "Modules\\{$item['module']}\\database\\seeders\\{$item['seeder']}";
            if (class_exists($seederClass)) {
                $this->info("  - Running {$item['module']}::{$item['seeder']}...");
                $this->call('db:seed', ['--class' => $seederClass]);
            }
        }

        $this->info('✓ Essential module data completed');

        $this->newLine();
        $this->info('===========================================');
        $this->info('   Production Setup Complete!');
        $this->info('===========================================');
        $this->newLine();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Super Admin Email', $email],
                ['Password', $password ?? '123456'],
                ['Default Team', 'Default Team (TM-001)'],
                ['Default Shift', 'Default Shift (09:00 - 18:00)'],
                ['Default Department', 'Default Department'],
                ['Default Designation', 'Default Designation'],
            ]
        );

        $this->newLine();
        $this->warn('⚠️  IMPORTANT: Please change the super admin password immediately after first login!');
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
            // Add other module permission seeders as needed
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
