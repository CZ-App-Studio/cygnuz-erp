<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StorePOSDemoSeeder extends Seeder
{
    /**
     * Run the store and POS demo seeders.
     */
    public function run(): void
    {
        $this->command->info('Starting Store & POS Demo Data Seeding...');

        // First, seed stores
        $this->command->info('Seeding stores...');
        $this->call(\Modules\StoreManager\Database\Seeders\StoresDemoSeeder::class);

        // Then, seed POS terminals and sessions
        $this->command->info('Seeding POS terminals and sessions...');
        $this->call(\Modules\POSSystem\Database\Seeders\POSTerminalsDemoSeeder::class);

        $this->command->info('Store & POS Demo Data Seeding completed!');
    }
}
