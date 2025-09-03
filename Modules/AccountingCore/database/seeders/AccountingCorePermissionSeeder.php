<?php

namespace Modules\AccountingCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccountingCorePermissionSeeder extends Seeder
{
    /**
     * Accounting Core module permissions organized by category
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
            // Define all Accounting Core permissions
            $this->definePermissions();

            // Create/update permissions
            $this->createPermissions();

            // Update existing roles with Accounting permissions
            $this->updateRolePermissions();

            DB::commit();
            $this->command->info('Accounting Core permissions seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding Accounting Core permissions: '.$e->getMessage());
        }
    }

    /**
     * Define all Accounting Core permissions by category
     */
    protected function definePermissions(): void
    {
        $this->permissions = [
            // Dashboard Access
            'Dashboard Access' => [
                ['name' => 'accountingcore.dashboard.view', 'description' => 'View accounting dashboard'],
                ['name' => 'accountingcore.dashboard.statistics', 'description' => 'View dashboard statistics'],
            ],

            // Transaction Management
            'Transaction Management' => [
                ['name' => 'accountingcore.transactions.index', 'description' => 'View all transactions'],
                ['name' => 'accountingcore.transactions.create', 'description' => 'Create new transactions'],
                ['name' => 'accountingcore.transactions.store', 'description' => 'Store new transactions'],
                ['name' => 'accountingcore.transactions.show', 'description' => 'View transaction details'],
                ['name' => 'accountingcore.transactions.edit', 'description' => 'Edit transactions'],
                ['name' => 'accountingcore.transactions.update', 'description' => 'Update transactions'],
                ['name' => 'accountingcore.transactions.destroy', 'description' => 'Delete transactions'],
                ['name' => 'accountingcore.transactions.delete-attachment', 'description' => 'Delete transaction attachments'],
            ],

            // Category Management
            'Category Management' => [
                ['name' => 'accountingcore.categories.index', 'description' => 'View all transaction categories'],
                ['name' => 'accountingcore.categories.create', 'description' => 'Create new categories'],
                ['name' => 'accountingcore.categories.store', 'description' => 'Store new categories'],
                ['name' => 'accountingcore.categories.show', 'description' => 'View category details'],
                ['name' => 'accountingcore.categories.edit', 'description' => 'Edit categories'],
                ['name' => 'accountingcore.categories.update', 'description' => 'Update categories'],
                ['name' => 'accountingcore.categories.destroy', 'description' => 'Delete categories'],
                ['name' => 'accountingcore.categories.search', 'description' => 'Search categories for dropdowns'],
            ],

            // Tax Rate Management
            'Tax Rate Management' => [
                ['name' => 'accountingcore.tax-rates.index', 'description' => 'View all tax rates'],
                ['name' => 'accountingcore.tax-rates.create', 'description' => 'Create new tax rates'],
                ['name' => 'accountingcore.tax-rates.store', 'description' => 'Store new tax rates'],
                ['name' => 'accountingcore.tax-rates.show', 'description' => 'View tax rate details'],
                ['name' => 'accountingcore.tax-rates.edit', 'description' => 'Edit tax rates'],
                ['name' => 'accountingcore.tax-rates.update', 'description' => 'Update tax rates'],
                ['name' => 'accountingcore.tax-rates.destroy', 'description' => 'Delete tax rates'],
                ['name' => 'accountingcore.tax-rates.active', 'description' => 'View active tax rates for dropdowns'],
            ],

            // Reports Management
            'Reports Management' => [
                ['name' => 'accountingcore.reports.index', 'description' => 'View reports dashboard'],
                ['name' => 'accountingcore.reports.generate', 'description' => 'Generate custom reports'],
                ['name' => 'accountingcore.reports.export', 'description' => 'Export reports'],
                ['name' => 'accountingcore.reports.summary', 'description' => 'View income & expense summary report'],
                ['name' => 'accountingcore.reports.summary.export-pdf', 'description' => 'Export summary report as PDF'],
                ['name' => 'accountingcore.reports.cashflow', 'description' => 'View cash flow report'],
                ['name' => 'accountingcore.reports.cashflow.export-pdf', 'description' => 'Export cash flow report as PDF'],
                ['name' => 'accountingcore.reports.category-performance', 'description' => 'View category performance report'],
            ],

            // Module Access
            'Module Access' => [
                ['name' => 'accountingcore.access', 'description' => 'Access Accounting Core module'],
                ['name' => 'accountingcore.settings', 'description' => 'Manage accounting settings'],
            ],
        ];
    }

    /**
     * Create all permissions in the database
     */
    protected function createPermissions(): void
    {
        $sortOrder = 2000; // Start from 2000 to avoid conflicts with existing permissions

        foreach ($this->permissions as $category => $categoryPermissions) {
            foreach ($categoryPermissions as $permission) {
                Permission::updateOrCreate(
                    [
                        'name' => $permission['name'],
                        'guard_name' => 'web',
                    ],
                    [
                        'module' => 'AccountingCore',
                        'description' => $permission['description'].' ('.$category.')',
                        'sort_order' => $sortOrder++,
                    ]
                );

                $this->command->info("Created/Updated permission: {$permission['name']} (AccountingCore - {$category})");
            }
        }
    }

    /**
     * Update existing roles with appropriate Accounting permissions
     */
    protected function updateRolePermissions(): void
    {
        // Accounting Manager - Full Accounting access
        $this->updateAccountingManagerPermissions();

        // Accounting Executive - Limited Accounting access
        $this->updateAccountingExecutivePermissions();

        // Accountant - Comprehensive accounting access
        $this->updateAccountantPermissions();

        // Admin roles
        $this->updateAdminPermissions();
    }

    protected function updateAccountingManagerPermissions(): void
    {
        $role = Role::where('name', 'accounting_manager')->first();
        if (! $role) {
            return;
        }

        // Get all AccountingCore permissions
        $permissions = Permission::where('module', 'AccountingCore')->pluck('name')->toArray();

        // Add existing permissions that should remain
        $existingPermissions = $role->permissions()
            ->whereNotIn('module', ['AccountingCore'])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions(array_merge($permissions, $existingPermissions));
        $this->command->info('Updated Accounting Manager role with AccountingCore permissions');
    }

    protected function updateAccountingExecutivePermissions(): void
    {
        $role = Role::where('name', 'accounting_executive')->first();
        if (! $role) {
            return;
        }

        $permissions = [
            // Dashboard - Limited access
            'accountingcore.dashboard.view',
            'accountingcore.dashboard.statistics',

            // Transactions - Full access
            'accountingcore.transactions.index',
            'accountingcore.transactions.create',
            'accountingcore.transactions.store',
            'accountingcore.transactions.show',
            'accountingcore.transactions.edit',
            'accountingcore.transactions.update',

            // Categories - View and search only
            'accountingcore.categories.index',
            'accountingcore.categories.show',
            'accountingcore.categories.search',

            // Tax Rates - View and search only
            'accountingcore.tax-rates.index',
            'accountingcore.tax-rates.show',
            'accountingcore.tax-rates.active',

            // Reports - View access
            'accountingcore.reports.index',
            'accountingcore.reports.summary',
            'accountingcore.reports.cashflow',
            'accountingcore.reports.category-performance',

            // Module access
            'accountingcore.access',
        ];

        // Add existing non-AccountingCore permissions
        $existingPermissions = $role->permissions()
            ->whereNotIn('module', ['AccountingCore'])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions(array_merge($permissions, $existingPermissions));
        $this->command->info('Updated Accounting Executive role with limited AccountingCore permissions');
    }

    protected function updateAccountantPermissions(): void
    {
        $role = Role::where('name', 'accountant')->first();
        if (! $role) {
            return;
        }

        $permissions = [
            // Dashboard - Full access
            'accountingcore.dashboard.view',
            'accountingcore.dashboard.statistics',

            // Transactions - Full access
            'accountingcore.transactions.index',
            'accountingcore.transactions.create',
            'accountingcore.transactions.store',
            'accountingcore.transactions.show',
            'accountingcore.transactions.edit',
            'accountingcore.transactions.update',
            'accountingcore.transactions.destroy',
            'accountingcore.transactions.delete-attachment',

            // Categories - View and create access
            'accountingcore.categories.index',
            'accountingcore.categories.create',
            'accountingcore.categories.store',
            'accountingcore.categories.show',
            'accountingcore.categories.edit',
            'accountingcore.categories.update',
            'accountingcore.categories.search',

            // Tax Rates - View only
            'accountingcore.tax-rates.index',
            'accountingcore.tax-rates.show',
            'accountingcore.tax-rates.active',

            // Reports - Full access
            'accountingcore.reports.index',
            'accountingcore.reports.generate',
            'accountingcore.reports.export',
            'accountingcore.reports.summary',
            'accountingcore.reports.summary.export-pdf',
            'accountingcore.reports.cashflow',
            'accountingcore.reports.cashflow.export-pdf',
            'accountingcore.reports.category-performance',

            // Module access
            'accountingcore.access',
        ];

        // Add existing non-AccountingCore permissions
        $existingPermissions = $role->permissions()
            ->whereNotIn('module', ['AccountingCore'])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions(array_merge($permissions, $existingPermissions));
        $this->command->info('Updated Accountant role with comprehensive AccountingCore permissions');
    }

    protected function updateAdminPermissions(): void
    {
        // Super Admin gets all permissions
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
            $this->command->info('Updated Super Admin role with all permissions');
        }

        // Admin gets all AccountingCore permissions
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $accountingPermissions = Permission::where('module', 'AccountingCore')->pluck('name')->toArray();
            $existingPermissions = $admin->permissions()
                ->whereNotIn('module', ['AccountingCore'])
                ->pluck('name')
                ->toArray();

            $admin->syncPermissions(array_merge($accountingPermissions, $existingPermissions));
            $this->command->info('Updated Admin role with all AccountingCore permissions');
        }
    }
}
