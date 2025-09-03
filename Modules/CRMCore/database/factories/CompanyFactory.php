<?php

namespace Modules\CRMCore\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRMCore\app\Models\Company;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // A predefined list of industries for more realistic data
        $industries = ['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing', 'Consulting', 'Real Estate', 'Education'];

        return [
            'name' => fake()->company(),
            'website' => 'https://'.fake()->unique()->domainName(),
            'phone_office' => fake()->phoneNumber(),
            'email_office' => fake()->unique()->companyEmail(),

            // Address Fields
            'address_street' => fake()->streetAddress(),
            'address_city' => fake()->city(),
            'address_state' => fake()->state(),
            'address_postal_code' => fake()->postcode(),
            'address_country' => fake()->country(),

            'industry' => fake()->randomElement($industries),
            'description' => fake()->bs(), // Uses Faker's business "bullsh*t" generator for a short description
            'is_active' => fake()->boolean(90), // 90% of companies will be active

            // We will assign a user in the Seeder file for better control
            // 'assigned_to_user_id' will be set there.
            // created_by_id and updated_by_id are handled by your UserActionsTrait on model creation.
            // tenant_id will be null by default as per our migration.
        ];
    }
}
