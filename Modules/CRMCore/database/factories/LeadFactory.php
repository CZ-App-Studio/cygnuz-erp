<?php

namespace Modules\CRMCore\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRMCore\app\Models\Lead;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Realistic lead titles
        $leadTitles = [
            'Inquiry about Enterprise Plan',
            'Follow-up from Dubai Web Summit',
            'Request for a custom quote',
            'Demo request for CRM features',
            'Partnership Opportunity Discussion',
            'Question regarding API integration',
        ];

        return [
            'title' => fake()->randomElement($leadTitles),
            'description' => fake()->paragraph(2),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'company_name' => fake()->optional(0.8)->company(), // 80% of leads have a company name
            'value' => fake()->randomElement([500, 1000, 2500, 5000, 10000, 15000, 20000]),

            // Foreign keys like lead_source_id, lead_status_id, assigned_to_user_id
            // will be set in the Seeder for better control and realism.

            // Conversion fields are null by default for new leads
            'converted_at' => null,
            'converted_to_contact_id' => null,
            // TODO: Uncomment when deals table is created
            // 'converted_to_deal_id' => null,

            // created_by_id, updated_by_id, tenant_id are handled by your traits
        ];
    }
}
