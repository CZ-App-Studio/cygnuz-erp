<?php

namespace Modules\AccountingCore\database\seeders;

use Illuminate\Database\Seeder;
use Modules\AccountingCore\app\Models\TaxRate;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxRates = [
            [
                'name' => 'No Tax',
                'rate' => 0,
                'type' => 'percentage',
                'is_default' => true,
                'is_active' => true,
                'description' => 'No tax applied',
                'tax_authority' => 'None'
            ],
            [
                'name' => 'Sales Tax 5%',
                'rate' => 5,
                'type' => 'percentage',
                'is_default' => false,
                'is_active' => true,
                'description' => 'Standard sales tax rate',
                'tax_authority' => 'State'
            ],
            [
                'name' => 'Sales Tax 10%',
                'rate' => 10,
                'type' => 'percentage',
                'is_default' => false,
                'is_active' => true,
                'description' => 'High sales tax rate',
                'tax_authority' => 'State'
            ],
            [
                'name' => 'GST 18%',
                'rate' => 18,
                'type' => 'percentage',
                'is_default' => false,
                'is_active' => true,
                'description' => 'Goods and Services Tax',
                'tax_authority' => 'Federal'
            ],
            [
                'name' => 'VAT 20%',
                'rate' => 20,
                'type' => 'percentage',
                'is_default' => false,
                'is_active' => true,
                'description' => 'Value Added Tax',
                'tax_authority' => 'Federal'
            ],
            [
                'name' => 'Service Tax 15%',
                'rate' => 15,
                'type' => 'percentage',
                'is_default' => false,
                'is_active' => true,
                'description' => 'Tax on services',
                'tax_authority' => 'Federal'
            ],
            [
                'name' => 'Flat Fee $25',
                'rate' => 25,
                'type' => 'fixed',
                'is_default' => false,
                'is_active' => true,
                'description' => 'Fixed tax amount',
                'tax_authority' => 'State'
            ]
        ];

        foreach ($taxRates as $taxRate) {
            TaxRate::firstOrCreate(
                ['name' => $taxRate['name']],
                $taxRate
            );
        }
    }
}