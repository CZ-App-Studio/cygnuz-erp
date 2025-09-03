<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class MigrateHRPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:migrate-permissions 
                            {--dry-run : Run in dry-run mode to see what would be changed}
                            {--force : Force migration without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate HR-related permissions from ERPPermissionSeeder to HRCore module';

    /**
     * HR-related permissions to migrate
     *
     * @var array
     */
    protected $hrPermissions = [
        // HRCore permissions that should be moved
        'view-employees',
        'create-employees',
        'edit-employees',
        'delete-employees',
        'view-employee-details',
        'manage-employee-permissions',
        'export-employee-data',

        // Attendance
        'view-attendance',
        'create-attendance',
        'edit-attendance',
        'delete-attendance',
        'approve-attendance',
        'view-attendance-reports',
        'export-attendance-data',

        // Leaves
        'view-leaves',
        'create-leave',
        'edit-leave',
        'delete-leave',
        'approve-leave',
        'reject-leave',
        'view-leave-balance',
        'manage-leave-balance',
        'view-leave-reports',

        // Departments
        'view-departments',
        'create-departments',
        'edit-departments',
        'delete-departments',

        // Designations
        'view-designations',
        'create-designations',
        'edit-designations',
        'delete-designations',

        // Teams
        'view-teams',
        'create-teams',
        'edit-teams',
        'delete-teams',
        'manage-team-members',

        // Shifts
        'view-shifts',
        'create-shifts',
        'edit-shifts',
        'delete-shifts',

        // Holidays
        'view-holidays',
        'create-holidays',
        'edit-holidays',
        'delete-holidays',

        // Expenses
        'view-expenses',
        'create-expense',
        'edit-expense',
        'delete-expense',
        'approve-expense',
        'reject-expense',

        // Payroll (if exists)
        'view-payroll',
        'process-payroll',
        'manage-salary',
        'generate-payslips',
        'view-payslips',

        // Loans
        'view-loans',
        'create-loan',
        'edit-loan',
        'delete-loan',
        'approve-loan',
        'reject-loan',

        // Reports
        'view-hr-reports',
        'generate-hr-reports',
        'export-hr-reports',

        // Organization
        'view-organization-hierarchy',

        // Settings
        'manage-hr-settings',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->option('force') && ! $this->option('dry-run')) {
            if (! $this->confirm('This will migrate HR permissions to HRCore module. Continue?')) {
                $this->info('Migration cancelled.');

                return 0;
            }
        }

        $this->info('Starting HR permissions migration...');

        if ($this->option('dry-run')) {
            $this->warn('Running in DRY-RUN mode. No changes will be made.');
        }

        DB::beginTransaction();

        try {
            $migratedCount = 0;
            $notFoundCount = 0;

            foreach ($this->hrPermissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)
                    ->where('guard_name', 'web')
                    ->first();

                if ($permission) {
                    // Check if already in HRCore
                    if ($permission->module === 'HRCore') {
                        $this->line("✓ {$permissionName} - Already in HRCore module");

                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->info("Would migrate: {$permissionName} from {$permission->module} to HRCore");
                    } else {
                        $oldModule = $permission->module;
                        $permission->module = 'HRCore';
                        $permission->save();

                        $this->info("✓ Migrated: {$permissionName} from {$oldModule} to HRCore");
                    }

                    $migratedCount++;
                } else {
                    $this->warn("✗ Not found: {$permissionName}");
                    $notFoundCount++;
                }
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->info("\nDry run completed:");
            } else {
                DB::commit();
                $this->info("\nMigration completed:");
            }

            $this->info("- Permissions migrated: {$migratedCount}");
            $this->info("- Permissions not found: {$notFoundCount}");

            if (! $this->option('dry-run')) {
                $this->info("\nNext steps:");
                $this->info("1. Run 'php artisan db:seed --class=Modules\\HRCore\\Database\\Seeders\\HRCorePermissionSeeder'");
                $this->info("2. Clear permission cache: 'php artisan permission:cache-reset'");
                $this->info('3. Test the application to ensure permissions work correctly');
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Migration failed: '.$e->getMessage());

            return 1;
        }
    }
}
