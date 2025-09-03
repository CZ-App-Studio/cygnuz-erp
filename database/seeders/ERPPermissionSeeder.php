<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class ERPPermissionSeeder extends Seeder
{
    /**
     * All ERP permissions organized by module
     */
    protected $permissions = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Start transaction
        DB::beginTransaction();

        try {
            // Define all permissions
            $this->definePermissions();

            // Create permissions
            $this->createPermissions();

            DB::commit();
            $this->command->info('ERP permissions seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding permissions: '.$e->getMessage());
        }
    }

    /**
     * Define all ERP permissions by module
     */
    protected function definePermissions(): void
    {
        $this->permissions = [
            // Dashboard Module
            'Dashboard' => [
                ['name' => 'view-dashboard', 'description' => 'View main dashboard'],
                ['name' => 'view-reports', 'description' => 'View reports dashboard'],
            ],

            // User Management Module
            'AccessControl' => [

                // Roles & Permissions
                ['name' => 'view-roles', 'description' => 'View all roles'],
                ['name' => 'create-roles', 'description' => 'Create new roles'],
                ['name' => 'edit-roles', 'description' => 'Edit roles'],
                ['name' => 'delete-roles', 'description' => 'Delete roles'],
                ['name' => 'manage-role-permissions', 'description' => 'Manage role permissions'],
                ['name' => 'view-permissions', 'description' => 'View all permissions'],
                ['name' => 'create-permissions', 'description' => 'Create new permissions'],
                ['name' => 'delete-permissions', 'description' => 'Delete permissions'],
                ['name' => 'manage-permissions', 'description' => 'Full permission management'],
            ],

            // System Administration Module
            'SystemAdministration' => [
                // Settings
                ['name' => 'manage-general-settings', 'description' => 'Manage general settings'],
                ['name' => 'manage-email-settings', 'description' => 'Manage email settings'],
                ['name' => 'manage-payment-settings', 'description' => 'Manage payment settings'],
                ['name' => 'manage-api-settings', 'description' => 'Manage API settings'],
                ['name' => 'view-system-status', 'description' => 'View system status'],

                // Notifications
                ['name' => 'manage-notifications', 'description' => 'Manage notification settings'],
                ['name' => 'send-bulk-notifications', 'description' => 'Send bulk notifications'],
            ],

        ];
    }

    /**
     * Create all permissions in the database
     */
    protected function createPermissions(): void
    {
        $sortOrder = 0;

        foreach ($this->permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $permission) {
                Permission::updateOrCreate(
                    [
                        'name' => $permission['name'],
                        'guard_name' => 'web',
                    ],
                    [
                        'module' => $module,
                        'description' => $permission['description'],
                        'sort_order' => $sortOrder++,
                    ]
                );

                $this->command->info("Created/Updated permission: {$permission['name']} ({$module})");
            }
        }
    }
}
