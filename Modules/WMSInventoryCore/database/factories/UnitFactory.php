<?php

namespace Modules\WMSInventoryCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\WMSInventoryCore\Models\Unit;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Piece', 'Kilogram', 'Meter', 'Liter', 'Box', 'Pack']),
            'symbol' => $this->faker->randomElement(['pcs', 'kg', 'm', 'L', 'box', 'pack']),
            'conversion_factor' => $this->faker->randomFloat(4, 0.0001, 1000),
            'is_base_unit' => $this->faker->boolean(20), // 20% chance of being base unit
            'created_by_id' => 1,
            'updated_by_id' => 1,
        ];
    }
}
