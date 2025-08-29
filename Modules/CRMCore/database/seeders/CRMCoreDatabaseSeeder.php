<?php

namespace Modules\CRMCore\Database\Seeders;

use Illuminate\Database\Seeder;

class CRMCoreDatabaseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->call([
      CRMCoreSettingsSeeder::class,
      CustomerGroupSeeder::class,
      CompanySeeder::class,
      ContactSeeder::class,
      CustomerSeeder::class,
      LeadSeeder::class,
      DealSeeder::class,
      TaskSeeder::class
    ]);
  }
}
