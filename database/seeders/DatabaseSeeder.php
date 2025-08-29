<?php

namespace Database\Seeders;

use App\Enums\DomainRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Models\SuperAdmin\DomainRequest;
use App\Models\SuperAdmin\Order;
use App\Models\SuperAdmin\Plan;
use App\Models\SuperAdmin\Subscription;
use App\Models\User;
use App\Services\PlanService\ISubscriptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    Artisan::call('cache:clear');

    $this->command->info('====================================');
    $this->command->info('   Starting Database Seeding');
    $this->command->info('====================================');

    // Step 1: Seed basic permissions first
    $this->command->info('Step 1: Seeding permissions...');
    $this->call(ERPPermissionSeeder::class);

    // Step 2: Run module permission seeders
    $this->command->info('Step 2: Seeding module permissions...');
    $this->runModuleSeeders();

    // Step 3: Create roles after all permissions are available
    $this->command->info('Step 3: Creating roles...');
    $this->call(ERPRoleSeeder::class);

    // Step 4: Seed system settings
    $this->command->info('Step 4: Seeding system settings...');
    $this->call(SystemSettingsSeeder::class);

    // Step 5: Seed HRCore base data
    $this->command->info('Step 5: Seeding HRCore base data...');
    if (class_exists('\Modules\HRCore\database\seeders\LeaveTypeSeeder')) {
        $this->call(\Modules\HRCore\database\seeders\LeaveTypeSeeder::class);
    }
    if (class_exists('\Modules\HRCore\database\seeders\ExpenseTypeSeeder')) {
        $this->call(\Modules\HRCore\database\seeders\ExpenseTypeSeeder::class);
    }
    if (class_exists('\Modules\HRCore\database\seeders\HRCoreSettingsSeeder')) {
        $this->call(\Modules\HRCore\database\seeders\HRCoreSettingsSeeder::class);
    }

    // Step 6: Seed HRCore demo data (teams, shifts, departments, etc.)
    $this->command->info('Step 6: Seeding HRCore demo data...');
    if (class_exists('\Modules\HRCore\database\seeders\HRCoreDemoDataSeeder')) {
        $this->call(\Modules\HRCore\database\seeders\HRCoreDemoDataSeeder::class);
    }

    // Step 7: Create demo user accounts
    $this->command->info('Step 7: Creating demo user accounts...');
    $this->call(DemoSeeder::class);

    // Step 8: Seed tenant demo if enabled
    if (class_exists('\Modules\MultiTenancyCore\Database\Seeders\TenantDemoSeeder')) {
        $this->command->info('Step 8: Seeding multi-tenancy demo data...');
        $this->call(\Modules\MultiTenancyCore\Database\Seeders\TenantDemoSeeder::class);
    }

    $this->command->info('====================================');
    $this->command->info('   Database Seeding Complete!');
    $this->command->info('====================================');
  }

  /**
   * Run module-specific seeders that may add permissions
   */
  protected function runModuleSeeders(): void
  {
      // Run HRCore permission seeder if it exists
      if (class_exists('\Modules\HRCore\Database\Seeders\HRCorePermissionSeeder')) {
          $this->call(\Modules\HRCore\Database\Seeders\HRCorePermissionSeeder::class);
      }

      // Run other core module permission seeders
      $modulePermissionSeeders = [
          '\Modules\AccountingCore\Database\Seeders\AccountingCorePermissionSeeder',
          '\Modules\CRMCore\Database\Seeders\CRMCorePermissionSeeder',
          '\Modules\PMCore\Database\Seeders\PMCorePermissionSeeder',
          '\Modules\WMSInventoryCore\Database\Seeders\WMSInventoryCorePermissionSeeder',
          '\Modules\AICore\Database\Seeders\AICorePermissionSeeder',
          '\Modules\FileManagerCore\Database\Seeders\FileManagerCorePermissionSeeder',
      ];

      foreach ($modulePermissionSeeders as $seederClass) {
          if (class_exists($seederClass)) {
              $this->call($seederClass);
          }
      }
  }

}
