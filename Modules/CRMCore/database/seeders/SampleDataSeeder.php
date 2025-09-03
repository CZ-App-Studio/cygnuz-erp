<?php

namespace Modules\CRMCore\database\seeders;

use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Sample data...');

        $this->call([
            CompanySeeder::class,
            ContactSeeder::class,
            LeadSeeder::class,
            DealSeeder::class,
            TaskSeeder::class,
        ]);

        $this->command->info('Sample data seeded successfully!');
    }
}
