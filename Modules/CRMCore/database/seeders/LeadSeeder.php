<?php

namespace Modules\CRMCore\database\seeders;

use App\Enums\UserAccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\CRMCore\app\Models\Lead;
use Modules\CRMCore\app\Models\LeadSource;
use Modules\CRMCore\app\Models\LeadStatus;

class LeadSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // --- 1. Seed Lead Sources (Create if they don't exist) ---
    $this->command->info('Seeding Lead Sources...');
    $sources = ['Website Form', 'Referral', 'Cold Call', 'Trade Show', 'Partner', 'Advertisement'];
    foreach ($sources as $sourceName) {
      LeadSource::firstOrCreate(['name' => $sourceName]);
    }

    // --- 2. Seed Lead Statuses (Create if they don't exist) ---
    $this->command->info('Seeding Lead Statuses...');
    $statuses = [
      ['name' => 'New', 'color' => '#03a9f4', 'position' => 1, 'is_default' => true],
      ['name' => 'Contacted', 'color' => '#ff9800', 'position' => 2],
      ['name' => 'Proposal Sent', 'color' => '#9c27b0', 'position' => 3],
      ['name' => 'Qualified', 'color' => '#4caf50', 'position' => 4],
      ['name' => 'Unqualified', 'color' => '#f44336', 'position' => 5, 'is_final' => true],
      ['name' => 'Converted', 'color' => '#2196f3', 'position' => 6, 'is_final' => true],
    ];
    foreach ($statuses as $statusData) {
      LeadStatus::firstOrCreate(['name' => $statusData['name']], $statusData);
    }

    // --- 3. Seed Leads ---
    // Fetch required IDs for relationships
    $users = User::where('status', UserAccountStatus::ACTIVE)->pluck('id');
    $leadSourceIds = LeadSource::pluck('id');
    $leadStatusIds = LeadStatus::where('is_final', false)->pluck('id'); // Only seed leads into non-final statuses

    if ($users->isEmpty()) {
      $this->command->warn('No active users found. Skipping LeadSeeder. Please run UserSeeder first.');
      return;
    }

    $this->command->info('Seeding Leads...');
    $numberOfLeads = 100;
    $progressBar = $this->command->getOutput()->createProgressBar($numberOfLeads);

    Lead::factory()->count($numberOfLeads)->create([
      'lead_source_id' => fn() => $leadSourceIds->random(),
      'lead_status_id' => fn() => $leadStatusIds->random(),
      'assigned_to_user_id' => fn() => $users->random(),
    ]);

    $progressBar->finish();
    $this->command->info("\nLead seeding completed.");
  }
}
