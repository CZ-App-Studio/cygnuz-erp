<?php

namespace Modules\AICore\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AICorePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // AI Core Management
            ['name' => 'aicore.view', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'View AI Core', 'sort_order' => 1],
            ['name' => 'aicore.manage', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'Manage AI Core', 'sort_order' => 2],

            // AI Chat
            ['name' => 'aicore.chat.view', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'View AI Chat', 'sort_order' => 3],
            ['name' => 'aicore.chat.use', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'Use AI Chat', 'sort_order' => 4],

            // AI Models
            ['name' => 'aicore.models.view', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'View AI Models', 'sort_order' => 5],
            ['name' => 'aicore.models.manage', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'Manage AI Models', 'sort_order' => 6],

            // AI Providers
            ['name' => 'aicore.providers.view', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'View AI Providers', 'sort_order' => 7],
            ['name' => 'aicore.providers.manage', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'Manage AI Providers', 'sort_order' => 8],

            // AI Usage Analytics
            ['name' => 'aicore.usage.view', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'View AI Usage Analytics', 'sort_order' => 9],
            ['name' => 'aicore.usage.export', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'Export AI Usage Data', 'sort_order' => 10],

            // AI Settings
            ['name' => 'aicore.settings.view', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'View AI Settings', 'sort_order' => 11],
            ['name' => 'aicore.settings.manage', 'guard_name' => 'web', 'module' => 'AICore', 'description' => 'Manage AI Settings', 'sort_order' => 12],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => $permissionData['guard_name']],
                $permissionData
            );
        }

        // Assign permissions to Super Admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(array_column($permissions, 'name'));
        }

        // Assign permissions to Admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_column($permissions, 'name'));
        }

        $this->command->info('AICore permissions seeded successfully.');
    }
}
