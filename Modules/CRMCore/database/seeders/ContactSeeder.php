<?php

namespace Modules\CRMCore\database\seeders;

use App\Enums\UserAccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies and active users. These are required for seeding contacts.
        $companies = Company::pluck('id');
        $users = User::where('status', UserAccountStatus::ACTIVE)->pluck('id');

        if ($companies->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No companies or active users found. Skipping ContactSeeder. Please run CompanySeeder and UserSeeder first.');

            return;
        }

        $this->command->info('Seeding contacts for '.$companies->count().' companies...');
        $progressBar = $this->command->getOutput()->createProgressBar($companies->count());

        // For each company, create a random number of contacts (e.g., 1 to 5)
        foreach ($companies as $companyId) {
            $numberOfContacts = rand(1, 5);

            $contacts = Contact::factory()->count($numberOfContacts)->create([
                'company_id' => $companyId,
                'assigned_to_user_id' => $users->random(), // Assign a random user to each contact
            ]);

            // Designate the first created contact for this company as the primary
            $primaryContact = $contacts->first();
            if ($primaryContact) {
                $primaryContact->is_primary_contact_for_company = true;
                $primaryContact->save();
            }
            $progressBar->advance();
        }

        // Create some additional contacts that are not linked to any company
        Contact::factory()->count(25)->create([
            'company_id' => null,
            'assigned_to_user_id' => $users->random(),
        ]);

        $progressBar->finish();
        $this->command->info("\nContact seeding completed.");
    }
}
