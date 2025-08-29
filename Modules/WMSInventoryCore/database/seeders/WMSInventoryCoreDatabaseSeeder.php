<?php

namespace Modules\WMSInventoryCore\Database\Seeders;

use Illuminate\Database\Seeder;

class WMSInventoryCoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            WMSInventoryCorePermissionSeeder::class,
            SampleDataSeeder::class,
            WMSInventoryCoreSettingsSeeder::class,
            WMSInventoryCoreDemoSeeder::class,
            SalesDemoSeeder::class,
        ]);
    }
}
