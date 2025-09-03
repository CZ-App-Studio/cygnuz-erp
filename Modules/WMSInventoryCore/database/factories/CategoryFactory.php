<?php

namespace Modules\WMSInventoryCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WMSInventoryCore\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->unique()->bothify('CAT-##??##'),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'created_by_id' => 1,
            'updated_by_id' => 1,
        ];
    }
}
