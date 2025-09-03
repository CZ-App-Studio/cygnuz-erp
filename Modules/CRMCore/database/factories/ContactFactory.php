<?php

namespace Modules\CRMCore\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRMCore\app\Models\Contact;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jobTitles = ['Sales Manager', 'Marketing Coordinator', 'Lead Developer', 'Account Executive', 'IT Specialist', 'Support Representative', 'CEO', 'CFO'];
        $departments = ['Sales', 'Marketing', 'Technology', 'Finance', 'Support', 'Management'];
        $leadSources = ['Website', 'Referral', 'Cold Call', 'Trade Show', 'Partner', 'Advertisement'];

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email_primary' => fake()->unique()->safeEmail(),
            'email_secondary' => fake()->optional(0.2)->safeEmail(), // 20% chance of having a secondary email
            'phone_primary' => fake()->phoneNumber(),
            'phone_mobile' => fake()->optional(0.7)->phoneNumber(), // 70% chance of having a mobile number
            'phone_office' => fake()->optional(0.3)->phoneNumber(),

            // company_id and assigned_to_user_id will be set in the seeder
            'job_title' => fake()->randomElement($jobTitles),
            'department' => fake()->randomElement($departments),

            // Address Fields
            'address_street' => fake()->optional(0.6)->streetAddress(), // 60% chance of having an address
            'address_city' => fake()->city(),
            'address_state' => fake()->state(),
            'address_postal_code' => fake()->postcode(),
            'address_country' => fake()->country(),

            'date_of_birth' => fake()->optional(0.3)->date('Y-m-d', '-22 years'), // 30% chance, ensure they are adults
            'lead_source_name' => fake()->randomElement($leadSources),
            'description' => fake()->paragraph(1),

            'do_not_email' => fake()->boolean(10), // 10% chance
            'do_not_call' => fake()->boolean(10),
            'is_primary_contact_for_company' => false, // Set this logically in the seeder
            'is_active' => fake()->boolean(95), // 95% of contacts will be active

            // created_by_id, updated_by_id, tenant_id are handled by traits
        ];
    }
}
