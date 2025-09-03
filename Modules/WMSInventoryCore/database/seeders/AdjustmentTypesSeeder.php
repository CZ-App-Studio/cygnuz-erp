<?php

namespace Modules\WMSInventoryCore\database\seeders;

use Illuminate\Database\Seeder;
use Modules\WMSInventoryCore\Models\AdjustmentType;

class AdjustmentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adjustmentTypes = [
            [
                'name' => 'Stock Increase',
                'code' => 'INC',
                'effect' => 'increase',
                'description' => 'Increase inventory quantity',
                'status' => 'active',
            ],
            [
                'name' => 'Stock Decrease',
                'code' => 'DEC',
                'effect' => 'decrease',
                'description' => 'Decrease inventory quantity',
                'status' => 'active',
            ],
            [
                'name' => 'Damaged',
                'code' => 'DMG',
                'effect' => 'decrease',
                'description' => 'Items damaged and removed from stock',
                'status' => 'active',
            ],
            [
                'name' => 'Found',
                'code' => 'FND',
                'effect' => 'increase',
                'description' => 'Items found and added to stock',
                'status' => 'active',
            ],
        ];

        foreach ($adjustmentTypes as $type) {
            AdjustmentType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
