<?php

namespace Modules\WMSInventoryCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WMSInventoryCorePermissionSeeder extends Seeder
{
  /**
   * All WMSInventoryCore permissions organized by category
   */
  protected array $permissions = [];

  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Define all permissions
    $this->definePermissions();

    // Create permissions
    $this->createPermissions();

    // Update roles
    $this->updateRoles();

    $this->command->info('WMSInventoryCore permissions seeded successfully!');
  }

  /**
   * Define all WMS module permissions
   */
  protected function definePermissions(): void
  {
    $this->permissions = [
      // Products Management
      ['name' => 'wmsinventory.view-products', 'description' => 'View all inventory products'],
      ['name' => 'wmsinventory.create-product', 'description' => 'Create new inventory products'],
      ['name' => 'wmsinventory.edit-product', 'description' => 'Edit inventory products'],
      ['name' => 'wmsinventory.delete-product', 'description' => 'Delete inventory products'],
      ['name' => 'wmsinventory.search-products', 'description' => 'Search inventory products'],
      ['name' => 'wmsinventory.export-products', 'description' => 'Export products data'],

      // Warehouses Management
      ['name' => 'wmsinventory.view-warehouses', 'description' => 'View all warehouses'],
      ['name' => 'wmsinventory.create-warehouse', 'description' => 'Create new warehouses'],
      ['name' => 'wmsinventory.edit-warehouse', 'description' => 'Edit warehouses'],
      ['name' => 'wmsinventory.delete-warehouse', 'description' => 'Delete warehouses'],
      ['name' => 'wmsinventory.manage-warehouse-zones', 'description' => 'Manage warehouse zones'],
      ['name' => 'wmsinventory.view-warehouse-inventory', 'description' => 'View warehouse inventory levels'],

      // Categories Management
      ['name' => 'wmsinventory.view-categories', 'description' => 'View inventory categories'],
      ['name' => 'wmsinventory.create-category', 'description' => 'Create inventory categories'],
      ['name' => 'wmsinventory.edit-category', 'description' => 'Edit inventory categories'],
      ['name' => 'wmsinventory.delete-category', 'description' => 'Delete inventory categories'],

      // Units Management
      ['name' => 'wmsinventory.view-units', 'description' => 'View inventory units'],
      ['name' => 'wmsinventory.create-unit', 'description' => 'Create inventory units'],
      ['name' => 'wmsinventory.edit-unit', 'description' => 'Edit inventory units'],
      ['name' => 'wmsinventory.delete-unit', 'description' => 'Delete inventory units'],

      // Vendors Management
      ['name' => 'wmsinventory.view-vendors', 'description' => 'View all vendors'],
      ['name' => 'wmsinventory.create-vendor', 'description' => 'Create new vendors'],
      ['name' => 'wmsinventory.edit-vendor', 'description' => 'Edit vendors'],
      ['name' => 'wmsinventory.delete-vendor', 'description' => 'Delete vendors'],
      ['name' => 'wmsinventory.search-vendors', 'description' => 'Search vendors'],

      // Purchase Orders Management
      ['name' => 'wmsinventory.view-purchases', 'description' => 'View purchase orders'],
      ['name' => 'wmsinventory.create-purchase', 'description' => 'Create purchase orders'],
      ['name' => 'wmsinventory.edit-purchase', 'description' => 'Edit purchase orders'],
      ['name' => 'wmsinventory.delete-purchase', 'description' => 'Delete purchase orders'],
      ['name' => 'wmsinventory.approve-purchase', 'description' => 'Approve/reject purchase orders'],
      ['name' => 'wmsinventory.receive-purchase', 'description' => 'Receive purchase orders'],

      // Sales Orders Management
      ['name' => 'wmsinventory.view-sales', 'description' => 'View sales orders'],
      ['name' => 'wmsinventory.create-sale', 'description' => 'Create sales orders'],
      ['name' => 'wmsinventory.edit-sale', 'description' => 'Edit sales orders'],
      ['name' => 'wmsinventory.delete-sale', 'description' => 'Delete sales orders'],
      ['name' => 'wmsinventory.approve-sale', 'description' => 'Approve sales orders'],
      ['name' => 'wmsinventory.reject-sale', 'description' => 'Reject sales orders'],
      ['name' => 'wmsinventory.fulfill-sale', 'description' => 'Fulfill sales orders'],
      ['name' => 'wmsinventory.ship-sale', 'description' => 'Ship sales orders'],
      ['name' => 'wmsinventory.deliver-sale', 'description' => 'Deliver sales orders'],
      ['name' => 'wmsinventory.duplicate-sale', 'description' => 'Duplicate sales orders'],
      ['name' => 'wmsinventory.generate-invoice', 'description' => 'Generate invoices for sales orders'],
      ['name' => 'wmsinventory.search-customers', 'description' => 'Search customers'],

      // Adjustments Management
      ['name' => 'wmsinventory.view-adjustments', 'description' => 'View inventory adjustments'],
      ['name' => 'wmsinventory.create-adjustment', 'description' => 'Create inventory adjustments'],
      ['name' => 'wmsinventory.edit-adjustment', 'description' => 'Edit inventory adjustments'],
      ['name' => 'wmsinventory.delete-adjustment', 'description' => 'Delete inventory adjustments'],
      ['name' => 'wmsinventory.approve-adjustment', 'description' => 'Approve inventory adjustments'],

      // Adjustment Types Management
      ['name' => 'wmsinventory.view-adjustment-types', 'description' => 'View adjustment types'],
      ['name' => 'wmsinventory.create-adjustment-type', 'description' => 'Create adjustment types'],
      ['name' => 'wmsinventory.edit-adjustment-type', 'description' => 'Edit adjustment types'],
      ['name' => 'wmsinventory.delete-adjustment-type', 'description' => 'Delete adjustment types'],

      // Transfers Management
      ['name' => 'wmsinventory.view-transfers', 'description' => 'View inventory transfers'],
      ['name' => 'wmsinventory.create-transfer', 'description' => 'Create inventory transfers'],
      ['name' => 'wmsinventory.edit-transfer', 'description' => 'Edit inventory transfers'],
      ['name' => 'wmsinventory.delete-transfer', 'description' => 'Delete inventory transfers'],
      ['name' => 'wmsinventory.approve-transfer', 'description' => 'Approve inventory transfers'],
      ['name' => 'wmsinventory.ship-transfer', 'description' => 'Ship inventory transfers'],
      ['name' => 'wmsinventory.receive-transfer', 'description' => 'Receive inventory transfers'],
      ['name' => 'wmsinventory.cancel-transfer', 'description' => 'Cancel inventory transfers'],

      // Reports & Analytics
      ['name' => 'wmsinventory.view-reports', 'description' => 'View inventory reports'],
      ['name' => 'wmsinventory.view-inventory-valuation', 'description' => 'View inventory valuation reports'],
      ['name' => 'wmsinventory.view-stock-movement', 'description' => 'View stock movement reports'],
      ['name' => 'wmsinventory.view-low-stock', 'description' => 'View low stock reports'],
      ['name' => 'wmsinventory.export-reports', 'description' => 'Export inventory reports'],

      // Dashboard
      ['name' => 'wmsinventory.view-dashboard', 'description' => 'View inventory dashboard'],

      // Settings
      ['name' => 'wmsinventory.manage-settings', 'description' => 'Manage WMS settings'],
    ];
  }

  /**
   * Create all permissions in the database
   */
  protected function createPermissions(): void
  {
    foreach ($this->permissions as $permission) {
      try {
        $perm = Permission::firstOrCreate(
          [
            'name' => $permission['name'],
            'guard_name' => 'web'
          ],
          [
            'module' => 'WMSInventoryCore',
            'description' => $permission['description']
          ]
        );

        if ($perm->wasRecentlyCreated) {
          $this->command->info("Created permission: {$permission['name']}");
        } else {
          // Update the module and description if permission already exists
          $perm->update([
            'module' => 'WMSInventoryCore',
            'description' => $permission['description']
          ]);
          $this->command->info("Updated permission: {$permission['name']}");
        }
      } catch (\Exception $e) {
        $this->command->error("Failed to create/update permission {$permission['name']}: " . $e->getMessage());
      }
    }
  }

  /**
   * Update existing roles with WMS permissions
   */
  protected function updateRoles(): void
  {
    // Super Admin - Full access
    $this->updateSuperAdminRole();

    // Admin - Full WMS access
    $this->updateAdminRole();

    // Inventory Manager - Full WMS access except critical deletes
    $this->updateInventoryManagerRole();

    // Warehouse Staff - Operational access
    $this->updateWarehouseStaffRole();

    // Employee - View only access
    $this->updateEmployeeRole();
  }

  /**
   * Update Super Admin role
   */
  protected function updateSuperAdminRole(): void
  {
    try {
      $role = Role::where('name', 'super_admin')->first();
      if ($role) {
        $permissions = Permission::where('name', 'like', 'wmsinventory.%')->pluck('name')->toArray();
        if (!empty($permissions)) {
          $role->givePermissionTo($permissions);
          $this->command->info('Updated Super Admin role with all WMS permissions (' . count($permissions) . ' permissions)');
        }
      }
    } catch (\Exception $e) {
      $this->command->error('Failed to update Super Admin role: ' . $e->getMessage());
    }
  }

  /**
   * Update Admin role
   */
  protected function updateAdminRole(): void
  {
    try {
      $role = Role::where('name', 'admin')->first();
      if ($role) {
        $permissions = Permission::where('name', 'like', 'wmsinventory.%')->pluck('name')->toArray();
        $role->givePermissionTo($permissions);
        $this->command->info('Updated Admin role with WMS permissions');
      }
    } catch (\Exception $e) {
      $this->command->error('Failed to update Admin role: ' . $e->getMessage());
    }
  }

  /**
   * Create/Update Inventory Manager role
   */
  protected function updateInventoryManagerRole(): void
  {
    try {
      $role = Role::updateOrCreate(
        ['name' => 'inventory_manager', 'guard_name' => 'web'],
        [
          'description' => 'Manages warehouse operations, inventory levels, stock movements, and inventory reporting.'
        ]
      );

      // All permissions except critical deletes
      $permissions = [
        // Products - all except delete
        'wmsinventory.view-products',
        'wmsinventory.create-product',
        'wmsinventory.edit-product',
        'wmsinventory.search-products',
        'wmsinventory.export-products',

        // Warehouses - all except delete
        'wmsinventory.view-warehouses',
        'wmsinventory.create-warehouse',
        'wmsinventory.edit-warehouse',
        'wmsinventory.manage-warehouse-zones',
        'wmsinventory.view-warehouse-inventory',

        // Categories - all
        'wmsinventory.view-categories',
        'wmsinventory.create-category',
        'wmsinventory.edit-category',
        'wmsinventory.delete-category',

        // Units - all
        'wmsinventory.view-units',
        'wmsinventory.create-unit',
        'wmsinventory.edit-unit',
        'wmsinventory.delete-unit',

        // Vendors - all except delete
        'wmsinventory.view-vendors',
        'wmsinventory.create-vendor',
        'wmsinventory.edit-vendor',
        'wmsinventory.search-vendors',

        // Purchase Orders - all
        'wmsinventory.view-purchases',
        'wmsinventory.create-purchase',
        'wmsinventory.edit-purchase',
        'wmsinventory.delete-purchase',
        'wmsinventory.approve-purchase',
        'wmsinventory.receive-purchase',

        // Sales Orders - all
        'wmsinventory.view-sales',
        'wmsinventory.create-sale',
        'wmsinventory.edit-sale',
        'wmsinventory.delete-sale',
        'wmsinventory.approve-sale',
        'wmsinventory.reject-sale',
        'wmsinventory.fulfill-sale',
        'wmsinventory.ship-sale',
        'wmsinventory.deliver-sale',
        'wmsinventory.duplicate-sale',
        'wmsinventory.generate-invoice',
        'wmsinventory.search-customers',

        // Adjustments - all
        'wmsinventory.view-adjustments',
        'wmsinventory.create-adjustment',
        'wmsinventory.edit-adjustment',
        'wmsinventory.delete-adjustment',
        'wmsinventory.approve-adjustment',

        // Adjustment Types - all
        'wmsinventory.view-adjustment-types',
        'wmsinventory.create-adjustment-type',
        'wmsinventory.edit-adjustment-type',
        'wmsinventory.delete-adjustment-type',

        // Transfers - all
        'wmsinventory.view-transfers',
        'wmsinventory.create-transfer',
        'wmsinventory.edit-transfer',
        'wmsinventory.delete-transfer',
        'wmsinventory.approve-transfer',
        'wmsinventory.ship-transfer',
        'wmsinventory.receive-transfer',
        'wmsinventory.cancel-transfer',

        // Reports & Dashboard
        'wmsinventory.view-reports',
        'wmsinventory.view-inventory-valuation',
        'wmsinventory.view-stock-movement',
        'wmsinventory.view-low-stock',
        'wmsinventory.export-reports',
        'wmsinventory.view-dashboard',

        // Settings
        'wmsinventory.manage-settings',
      ];

      $role->syncPermissions($permissions);
      $this->command->info('Updated Inventory Manager role with WMS permissions');
    } catch (\Exception $e) {
      $this->command->error('Failed to update Inventory Manager role: ' . $e->getMessage());
    }
  }

  /**
   * Create/Update Warehouse Staff role
   */
  protected function updateWarehouseStaffRole(): void
  {
    try {
      $role = Role::updateOrCreate(
        ['name' => 'warehouse_staff', 'guard_name' => 'web'],
        [
          'description' => 'Handles daily warehouse operations including stock movements, transfers, and inventory counts.'
        ]
      );

      $permissions = [
        // Products - view and search only
        'wmsinventory.view-products',
        'wmsinventory.search-products',

        // Warehouses - view only
        'wmsinventory.view-warehouses',
        'wmsinventory.view-warehouse-inventory',

        // Categories & Units - view only
        'wmsinventory.view-categories',
        'wmsinventory.view-units',

        // Vendors - view and search only
        'wmsinventory.view-vendors',
        'wmsinventory.search-vendors',

        // Purchase Orders - operational permissions
        'wmsinventory.view-purchases',
        'wmsinventory.create-purchase',
        'wmsinventory.receive-purchase',

        // Sales Orders - operational permissions
        'wmsinventory.view-sales',
        'wmsinventory.create-sale',
        'wmsinventory.fulfill-sale',
        'wmsinventory.ship-sale',
        'wmsinventory.duplicate-sale',
        'wmsinventory.search-customers',

        // Adjustments - create and view
        'wmsinventory.view-adjustments',
        'wmsinventory.create-adjustment',

        // Adjustment Types - view only
        'wmsinventory.view-adjustment-types',

        // Transfers - operational permissions
        'wmsinventory.view-transfers',
        'wmsinventory.create-transfer',
        'wmsinventory.ship-transfer',
        'wmsinventory.receive-transfer',

        // Reports - basic viewing
        'wmsinventory.view-reports',
        'wmsinventory.view-stock-movement',
        'wmsinventory.view-low-stock',

        // Dashboard
        'wmsinventory.view-dashboard',
      ];

      $role->syncPermissions($permissions);
      $this->command->info('Updated Warehouse Staff role with WMS permissions');
    } catch (\Exception $e) {
      $this->command->error('Failed to update Warehouse Staff role: ' . $e->getMessage());
    }
  }

  /**
   * Update Employee role
   */
  protected function updateEmployeeRole(): void
  {
    try {
      $role = Role::where('name', 'employee')->first();
      if ($role) {
        $permissions = [
          // Basic view permissions
          'wmsinventory.view-products',
          'wmsinventory.search-products',
          'wmsinventory.view-warehouses',
          'wmsinventory.view-categories',
          'wmsinventory.view-units',
          'wmsinventory.view-vendors',
          'wmsinventory.search-vendors',
          'wmsinventory.view-purchases',
          'wmsinventory.view-sales',
          'wmsinventory.search-customers',
          'wmsinventory.view-dashboard',
        ];

        $role->givePermissionTo($permissions);
        $this->command->info('Updated Employee role with basic WMS view permissions');
      }
    } catch (\Exception $e) {
      $this->command->error('Failed to update Employee role: ' . $e->getMessage());
    }
  }
}
