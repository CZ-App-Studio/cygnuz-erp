<?php

namespace Modules\CRMCore\database\seeders;

use App\Models\User;
use App\Enums\UserAccountStatus;
use Illuminate\Database\Seeder;
use Modules\CRMCore\app\Models\Company;

class CompanySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Fetch all active users to assign companies to them.
    // It's assumed a UserSeeder has already run.
    $users = User::where('status', UserAccountStatus::ACTIVE)->pluck('id');

    if ($users->isEmpty()) {
      $this->command->warn('No active users found. Companies will not be assigned to any user. Please run UserSeeder first.');
      // Generate companies without assigning a user
      Company::factory()->count(50)->create();
      return;
    }

    // Use the factory to create 50 companies.
    // For each company being created, we assign a random active user as the owner.
    Company::factory()->count(50)->create([
      'assigned_to_user_id' => fn() => $users->random()
    ]);
  }
}
