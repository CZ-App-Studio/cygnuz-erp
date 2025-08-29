<?php

namespace Modules\FileManagerCore\Database\Seeders;

use Illuminate\Database\Seeder;

class FileManagerCoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            FileManagerCoreSettingsSeeder::class,
        ]);
    }
}
