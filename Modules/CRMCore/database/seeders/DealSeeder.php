<?php

namespace Modules\CRMCore\database\seeders;

use App\Enums\UserAccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;
use Modules\CRMCore\app\Models\Deal;
use Modules\CRMCore\app\Models\DealPipeline;
use Modules\CRMCore\app\Models\DealStage;

class DealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- 1. Seed Deal Pipelines and Stages (Create if they don't exist) ---
        $this->command->info('Seeding Deal Pipelines and Stages...');

        $standardPipeline = DealPipeline::firstOrCreate(
            ['name' => 'Standard Sales Pipeline'],
            [
                'description' => 'Default sales pipeline for most deals.',
                'is_default' => true,
                'position' => 1,
            ]
        );

        $stagesData = [
            ['name' => 'Qualification',        'color' => '#03a9f4', 'position' => 1, 'is_default_for_pipeline' => true],
            ['name' => 'Needs Analysis',       'color' => '#8bc34a', 'position' => 2],
            ['name' => 'Proposal Sent',        'color' => '#ff9800', 'position' => 3],
            ['name' => 'Negotiation/Review',   'color' => '#9c27b0', 'position' => 4],
            ['name' => 'Closed Won',           'color' => '#4caf50', 'position' => 5, 'is_won_stage' => true],
            ['name' => 'Closed Lost',          'color' => '#f44336', 'position' => 6, 'is_lost_stage' => true],
        ];

        foreach ($stagesData as $stageData) {
            DealStage::firstOrCreate(
                ['pipeline_id' => $standardPipeline->id, 'name' => $stageData['name']],
                array_merge($stageData, ['pipeline_id' => $standardPipeline->id])
            );
        }

        // You can add more pipelines and their stages here if needed

        // --- 2. Fetch required data for seeding Deals ---
        $activeUsers = User::where('status', UserAccountStatus::ACTIVE)->pluck('id');
        $companies = Company::pluck('id');
        $contacts = Contact::pluck('id'); // Fetch all contacts for broader random assignment
        $pipelines = DealPipeline::with('stages')->get(); // For accessing stages by pipeline

        if ($activeUsers->isEmpty() || $companies->isEmpty() || $contacts->isEmpty() || $pipelines->isEmpty()) {
            $this->command->warn('Not enough prerequisite data (Users, Companies, Contacts, or Pipelines/Stages) to seed Deals. Please run their respective seeders.');

            return;
        }

        // --- 3. Seed Deals ---
        $this->command->info('Seeding Deals...');
        $numberOfDeals = 75; // Create around 75 deals
        $progressBar = $this->command->getOutput()->createProgressBar($numberOfDeals);

        for ($i = 0; $i < $numberOfDeals; $i++) {
            $selectedPipeline = $pipelines->random();
            $dealStagesInPipeline = $selectedPipeline->stages; // Stages are already ordered by position

            // Distribute deals more towards earlier stages, fewer in closed stages
            $stageDistribution = [
                ...array_fill(0, 5, $dealStagesInPipeline->get(0)?->id), // New/Qualification
                ...array_fill(0, 4, $dealStagesInPipeline->get(1)?->id), // Needs Analysis
                ...array_fill(0, 3, $dealStagesInPipeline->get(2)?->id), // Proposal
                ...array_fill(0, 2, $dealStagesInPipeline->get(3)?->id), // Negotiation
                $dealStagesInPipeline->get(4)?->id, // Won
                $dealStagesInPipeline->get(5)?->id,  // Lost
            ];
            // Filter out nulls if a pipeline has less than 6 stages
            $stageDistribution = array_filter($stageDistribution);
            $selectedStageId = fake()->randomElement($stageDistribution);
            $selectedStage = $dealStagesInPipeline->firstWhere('id', $selectedStageId);

            $selectedCompany = $companies->random();
            // Attempt to get a contact from the selected company, or pick any contact
            $companyContacts = Contact::where('company_id', $selectedCompany)->pluck('id');
            $selectedContact = $companyContacts->isNotEmpty() ? $companyContacts->random() : $contacts->random();

            $dealData = [
                'pipeline_id' => $selectedPipeline->id,
                'deal_stage_id' => $selectedStageId,
                'company_id' => $selectedCompany,
                'contact_id' => $selectedContact,
                'assigned_to_user_id' => $activeUsers->random(),
                'probability' => null, // Default, can be set based on stage
                'actual_close_date' => null,
                'lost_reason' => null,
            ];

            if ($selectedStage) {
                if ($selectedStage->is_won_stage) {
                    $dealData['probability'] = 100;
                    $dealData['actual_close_date'] = fake()->dateTimeBetween('-3 months', '-1 day')->format('Y-m-d');
                } elseif ($selectedStage->is_lost_stage) {
                    $dealData['probability'] = 0;
                    $dealData['actual_close_date'] = fake()->dateTimeBetween('-3 months', '-1 day')->format('Y-m-d');
                    $dealData['lost_reason'] = fake()->randomElement(['Price too high', 'Lost to competitor', 'Project Cancelled', 'No response']);
                } else {
                    // Assign probability based on stage position
                    $stagePosition = $selectedStage->position;
                    $dealData['probability'] = min(90, $stagePosition * 20); // Example logic
                }
            }

            Deal::factory()->create($dealData);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->info("\nDeal seeding completed.");
    }
}
