<?php

namespace Modules\AccountingCore\Database\Seeders;

use Illuminate\Database\Seeder;

class AccountingCoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AccountingCorePermissionSeeder::class,
            \Modules\AccountingCore\database\seeders\TaxRateSeeder::class,
            AccountingCoreDemoSeeder::class,
        ]);
    }
}
