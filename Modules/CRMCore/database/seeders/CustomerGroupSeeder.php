<?php

namespace Modules\CRMCore\database\seeders;

use Illuminate\Database\Seeder;
use Modules\CRMCore\app\Models\CustomerGroup;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Regular',
                'code' => 'REGULAR',
                'description' => 'Standard customer group with no special privileges',
                'discount_percentage' => 0,
                'credit_limit' => 0,
                'payment_terms' => 'cod',
                'priority_support' => false,
                'free_shipping' => false,
                'min_order_amount' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'VIP',
                'code' => 'VIP',
                'description' => 'VIP customers with special benefits',
                'discount_percentage' => 10,
                'credit_limit' => 50000,
                'payment_terms' => 'net30',
                'priority_support' => true,
                'free_shipping' => true,
                'min_order_amount' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Wholesale',
                'code' => 'WHOLESALE',
                'description' => 'Wholesale customers with bulk discounts',
                'discount_percentage' => 15,
                'credit_limit' => 100000,
                'payment_terms' => 'net60',
                'priority_support' => true,
                'free_shipping' => false,
                'min_order_amount' => 1000,
                'is_active' => true,
            ],
            [
                'name' => 'Corporate',
                'code' => 'CORPORATE',
                'description' => 'Corporate accounts with custom terms',
                'discount_percentage' => 20,
                'credit_limit' => 500000,
                'payment_terms' => 'net60',
                'priority_support' => true,
                'free_shipping' => true,
                'min_order_amount' => 5000,
                'is_active' => true,
            ],
            [
                'name' => 'Staff',
                'code' => 'STAFF',
                'description' => 'Internal staff members',
                'discount_percentage' => 30,
                'credit_limit' => 0,
                'payment_terms' => 'cod',
                'priority_support' => false,
                'free_shipping' => false,
                'min_order_amount' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($groups as $group) {
            CustomerGroup::firstOrCreate(
                ['code' => $group['code']],
                $group
            );
        }
    }
}
