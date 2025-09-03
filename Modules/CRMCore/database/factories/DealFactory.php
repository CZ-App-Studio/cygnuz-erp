<?php

namespace Modules\CRMCore\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRMCore\app\Models\Deal;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Deal>
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dealTitles = [
            'New Website Development for',
            'CRM Implementation for',
            'Marketing Campaign Q3 for',
            'Software Upgrade for',
            'Consulting Services for',
            'Annual Maintenance Contract with',
        ];

        // These will be overridden in the seeder with actual existing IDs
        // but providing a fallback helps if factory is used directly for testing.
        $companyName = fake()->optional(0.9)->company(); // 90% chance to have a company name associated

        return [
            'title' => fake()->randomElement($dealTitles).' '.($companyName ?: fake()->firstName()."'s Project"),
            'description' => fake()->paragraph(2),
            'value' => fake()->randomElement([1000, 2500, 5000, 7500, 10000, 12500, 15000, 20000, 30000, 50000]),
            'expected_close_date' => fake()->dateTimeBetween('+2 weeks', '+4 months')->format('Y-m-d'),
            'actual_close_date' => null,
            'probability' => fake()->optional(0.7)->numberBetween(10, 90), // 70% chance to have a probability
            'lost_reason' => null,

            // Foreign keys (pipeline_id, deal_stage_id, company_id, contact_id, assigned_to_user_id)
            // will be set in the DealSeeder.

            // created_by_id, updated_by_id, tenant_id are handled by traits
        ];
    }
}
