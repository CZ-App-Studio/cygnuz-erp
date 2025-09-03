<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SeedStorePOSDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store-pos:demo-data 
                            {--fresh : Drop and recreate all store and POS tables before seeding}
                            {--stores-only : Only seed stores data}
                            {--terminals-only : Only seed terminals data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed demo data for Store Management and POS system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Store & POS Demo Data Seeding...');

        if ($this->option('fresh')) {
            if ($this->confirm('This will delete all existing store and POS data. Are you sure?')) {
                $this->freshMigrations();
            } else {
                return;
            }
        }

        if ($this->option('stores-only')) {
            $this->seedStores();
        } elseif ($this->option('terminals-only')) {
            $this->seedTerminals();
        } else {
            $this->seedStores();
            $this->seedTerminals();
        }

        $this->info('Demo data seeding completed successfully!');
        $this->displaySummary();
    }

    protected function freshMigrations()
    {
        $this->info('Running fresh migrations for StoreManager and POSAddon modules...');

        Artisan::call('module:migrate-refresh', [
            'module' => 'StoreManager',
            '--force' => true,
        ]);

        Artisan::call('module:migrate-refresh', [
            'module' => 'POSAddon',
            '--force' => true,
        ]);

        $this->info('Fresh migrations completed.');
    }

    protected function seedStores()
    {
        $this->info('Seeding stores demo data...');

        Artisan::call('db:seed', [
            '--class' => 'Modules\StoreManager\Database\Seeders\StoresDemoSeeder',
        ]);

        $this->info('Stores seeded successfully.');
    }

    protected function seedTerminals()
    {
        $this->info('Seeding POS terminals and sessions demo data...');

        // First ensure permissions are seeded
        Artisan::call('db:seed', [
            '--class' => 'Modules\POSAddon\Database\Seeders\POSPermissionSeeder',
        ]);

        // Then seed terminals
        Artisan::call('db:seed', [
            '--class' => 'Modules\POSAddon\Database\Seeders\POSTerminalsDemoSeeder',
        ]);

        $this->info('POS terminals and sessions seeded successfully.');
    }

    protected function displaySummary()
    {
        $storeCount = \Modules\StoreManager\app\Models\Store::count();
        $activeStoreCount = \Modules\StoreManager\app\Models\Store::where('is_active', true)->count();
        $terminalCount = DB::table('pos_terminals')->count();
        $activeTerminalCount = DB::table('pos_terminals')->where('is_active', true)->count();
        $sessionCount = DB::table('cash_register_sessions')->count();
        $openSessionCount = DB::table('cash_register_sessions')->where('status', 'open')->count();

        $this->info('');
        $this->info('=== Demo Data Summary ===');
        $this->table(
            ['Entity', 'Total', 'Active/Open'],
            [
                ['Stores', $storeCount, $activeStoreCount],
                ['POS Terminals', $terminalCount, $activeTerminalCount],
                ['Cash Sessions', $sessionCount, $openSessionCount.' open'],
            ]
        );

        $this->info('');
        $this->info('You can now:');
        $this->info('- Visit /storemanager/stores to manage stores');
        $this->info('- Visit /pos/terminals to manage POS terminals');
        $this->info('- Visit /pos/sessions to view cash register sessions');
        $this->info('');
        $this->info('Run "php artisan store-pos:demo-data --fresh" to reset and reseed all data.');
    }
}
