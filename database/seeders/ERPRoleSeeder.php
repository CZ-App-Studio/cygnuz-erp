<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ERPRoleSeeder extends Seeder
{
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
            // Create roles
            $this->createRoles();

            DB::commit();
            $this->command->info('ERP roles seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding roles: '.$e->getMessage());
        }
    }

    /**
     * Create comprehensive roles for the ERP system
     */
    protected function createRoles(): void
    {
        // Super Admin - Full access to everything
        $this->createSuperAdminRole();

        // Admin - Full access except system critical settings
        $this->createAdminRole();

        // Module-specific Manager Roles
        $this->createHRManagerRole();
        $this->createAccountingManagerRole();
        $this->createCRMManagerRole();
        $this->createProjectManagerRole();
        $this->createSalesManagerRole();

        // Module-specific Executive Roles
        $this->createHRExecutiveRole();
        $this->createAccountingExecutiveRole();
        $this->createSalesExecutiveRole();

        // Specialized Roles
        $this->createAccountantRole();
        $this->createInventoryManagerRole();
        $this->createPayrollManagerRole();

        // General Roles
        $this->createTeamLeaderRole();
        $this->createEmployeeRole();
        $this->createFieldEmployeeRole();
        $this->createClientRole();
    }

    /**
     * Create Super Admin role with all permissions
     */
    protected function createSuperAdminRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            [
                'description' => 'Complete system access with all permissions. Can manage system settings, users, and all modules.',
            ]
        );

        // Give all permissions
        $role->syncPermissions(Permission::all());
        $this->command->info('Super Admin role created/updated with all permissions');
    }

    /**
     * Create Admin role
     */
    protected function createAdminRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            [
                'description' => 'Administrative access to all modules except critical system settings. Can manage users and general operations.',
            ]
        );

        // All permissions except critical system settings
        $permissions = Permission::whereNotIn('name', [
            'manage-api-settings',
            'view-system-logs',
            'manage-email-settings',
        ])->pluck('name')->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Admin role created/updated');
    }

    /**
     * Create HR Manager role
     */
    protected function createHRManagerRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'hr_manager', 'guard_name' => 'web'],
            [
                'description' => 'Manages all HR operations including employees, attendance, leaves, payroll, and organizational structure.',
            ]
        );

        // Get HRCore permissions if they exist, otherwise use basic permissions
        $permissions = Permission::where('module', 'HRCore')
            ->orWhere('module', 'Organization')
            ->orWhereIn('name', [
                'view-dashboard',
                'view-announcements',
                'create-announcement',
            ])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('HR Manager role created/updated');
    }

    /**
     * Create Accounting Manager role
     */
    protected function createAccountingManagerRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'accounting_manager', 'guard_name' => 'web'],
            [
                'description' => 'Oversees all financial operations including invoicing, payments, accounting reports, and financial settings.',
            ]
        );

        $permissions = Permission::where('module', 'Accounting')
            ->orWhereIn('name', [
                'view-dashboard',
                'view-reports',
                'view-companies',
                'view-contacts',
                'manage-currencies',
                'update-exchange-rates',
            ])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Accounting Manager role created/updated');
    }

    /**
     * Create CRM Manager role
     */
    protected function createCRMManagerRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'crm_manager', 'guard_name' => 'web'],
            [
                'description' => 'Manages customer relationships, leads, deals, contacts, and sales pipeline activities.',
            ]
        );

        $permissions = Permission::where('module', 'CRM')
            ->orWhereIn('name', [
                'view-dashboard',
                'view-reports',
            ])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('CRM Manager role created/updated');
    }

    /**
     * Create Project Manager role
     */
    protected function createProjectManagerRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'project_manager', 'guard_name' => 'web'],
            [
                'description' => 'Manages projects, assigns tasks, monitors progress, and coordinates team activities.',
            ]
        );

        $permissions = Permission::where('module', 'ProjectManagement')
            ->orWhereIn('name', [
                'view-dashboard',
                'view-reports',
                'view-teams',
                'view-employees',
                'view-tasks',
                'create-task',
                'edit-task',
                'assign-task',
            ])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Project Manager role created/updated');
    }

    /**
     * Create Sales Manager role
     */
    protected function createSalesManagerRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'sales_manager', 'guard_name' => 'web'],
            [
                'description' => 'Manages sales operations, orders, targets, customer visits, and sales team performance.',
            ]
        );

        $permissions = Permission::where('module', 'Sales')
            ->orWhere('module', 'CRM')
            ->orWhereIn('name', [
                'view-dashboard',
                'view-reports',
                'view-products',
            ])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Sales Manager role created/updated');
    }

    /**
     * Create HR Executive role
     */
    protected function createHRExecutiveRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'hr_executive', 'guard_name' => 'web'],
            [
                'description' => 'Assists with HR operations, manages employee records, attendance, and leave processing.',
            ]
        );

        // Use only permissions that are likely to exist
        $permissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-announcements',
            'view-employees',
            'view-employee-details',
            'view-departments',
            'view-designations',
            'view-teams',
            'view-holidays',
        ])->pluck('name')->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('HR Executive role created/updated');
    }

    /**
     * Create Accounting Executive role
     */
    protected function createAccountingExecutiveRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'accounting_executive', 'guard_name' => 'web'],
            [
                'description' => 'Handles day-to-day accounting tasks including invoicing, payments, and expense tracking.',
            ]
        );

        $permissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-companies',
            'view-contacts',
        ])->pluck('name')->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Accounting Executive role created/updated');
    }

    /**
     * Create Sales Executive role
     */
    protected function createSalesExecutiveRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'sales_executive', 'guard_name' => 'web'],
            [
                'description' => 'Manages own sales activities, leads, deals, and customer relationships with field tracking.',
            ]
        );

        $permissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-products',
        ])->pluck('name')->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Sales Executive role created/updated');
    }

    /**
     * Create Accountant role
     */
    protected function createAccountantRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'accountant', 'guard_name' => 'web'],
            [
                'description' => 'Handles financial record keeping, journal entries, reconciliations, and basic accounting operations.',
            ]
        );

        $permissions = Permission::where('module', 'Accounting')
            ->orWhereIn('name', [
                'view-dashboard',
                'view-reports',
                'view-companies',
                'view-contacts',
                'view-currencies',
                'view-invoices',
                'view-payments',
                'view-expenses',
            ])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Accountant role created/updated');
    }

    /**
     * Create Inventory Manager role
     */
    protected function createInventoryManagerRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'inventory_manager', 'guard_name' => 'web'],
            [
                'description' => 'Manages inventory operations including stock levels, procurement, warehouse operations, and inventory reports.',
            ]
        );

        $permissions = Permission::where('module', 'Inventory')
            ->orWhere('module', 'WMSInventoryCore')
            ->orWhereIn('name', [
                'view-dashboard',
                'view-reports',
                'view-products',
                'manage-products',
                'view-inventory',
                'manage-inventory',
                'view-warehouses',
                'manage-warehouses',
                'view-stock-movements',
                'create-stock-adjustment',
            ])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Inventory Manager role created/updated');
    }

    /**
     * Create Payroll Manager role
     */
    protected function createPayrollManagerRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'payroll_manager', 'guard_name' => 'web'],
            [
                'description' => 'Manages payroll processing, salary calculations, benefits administration, and payroll reports.',
            ]
        );

        $permissions = Permission::where('module', 'Payroll')
            ->orWhereIn('name', [
                'view-dashboard',
                'view-reports',
                'view-employees',
                'view-employee-details',
                'view-salaries',
                'manage-salaries',
                'view-payroll',
                'process-payroll',
                'view-attendance',
                'view-leaves',
                'manage-benefits',
                'view-tax-settings',
            ])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Payroll Manager role created/updated');
    }

    /**
     * Create Team Leader role
     */
    protected function createTeamLeaderRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'team_leader', 'guard_name' => 'web'],
            [
                'description' => 'Leads teams, manages projects, assigns tasks, and monitors team performance.',
            ]
        );

        $permissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-teams',
            'view-holidays',
            'view-announcements',
        ])->pluck('name')->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Team Leader role created/updated');
    }

    /**
     * Create Employee role
     */
    protected function createEmployeeRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'employee', 'guard_name' => 'web'],
            [
                'description' => 'Standard employee with access to own records, attendance, leaves, tasks, and basic features.',
            ]
        );

        $permissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-holidays',
            'view-announcements',
            'use-ai-assistant',
        ])->pluck('name')->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Employee role created/updated');
    }

    /**
     * Create Field Employee role
     */
    protected function createFieldEmployeeRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'field_employee', 'guard_name' => 'web'],
            [
                'description' => 'Mobile-only access for field workers with location tracking, visits, and multiple check-ins.',
            ]
        );

        $permissions = Permission::whereIn('name', [
            'view-holidays',
            'view-announcements',
        ])->pluck('name')->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Field Employee role created/updated');
    }

    /**
     * Create Client role
     */
    protected function createClientRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'client', 'guard_name' => 'web'],
            [
                'description' => 'External client access to view invoices, proposals, projects, and place orders.',
            ]
        );

        $permissions = Permission::whereIn('name', [
            'view-products',
            'view-announcements',
        ])->pluck('name')->toArray();

        $role->syncPermissions($permissions);
        $this->command->info('Client role created/updated');
    }
}
