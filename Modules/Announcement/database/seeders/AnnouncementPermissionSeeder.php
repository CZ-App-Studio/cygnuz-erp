<?php

namespace Modules\Announcement\database\seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AnnouncementPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for announcements
        $permissions = [
            // Announcement Management
            'announcements.view' => 'View announcements',
            'announcements.view.all' => 'View all announcements',
            'announcements.create' => 'Create announcements',
            'announcements.edit' => 'Edit announcements',
            'announcements.edit.all' => 'Edit all announcements',
            'announcements.delete' => 'Delete announcements',
            'announcements.delete.all' => 'Delete all announcements',
            'announcements.publish' => 'Publish announcements',
            'announcements.pin' => 'Pin/Unpin announcements',
            'announcements.acknowledge' => 'Acknowledge announcements',
            
            // Announcement Targeting
            'announcements.target.all' => 'Target all employees',
            'announcements.target.departments' => 'Target specific departments',
            'announcements.target.teams' => 'Target specific teams',
            'announcements.target.users' => 'Target specific users',
            
            // Announcement Features
            'announcements.send.email' => 'Send announcement emails',
            'announcements.send.notification' => 'Send announcement notifications',
            'announcements.require.acknowledgment' => 'Require acknowledgment',
            'announcements.upload.attachment' => 'Upload attachments',
            
            // Announcement Reports
            'announcements.reports.view' => 'View announcement reports',
            'announcements.reports.read_tracking' => 'View read tracking',
            'announcements.reports.acknowledgment' => 'View acknowledgment reports',
        ];

        foreach ($permissions as $permission => $description) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['module' => 'Announcement', 'description' => $description]
            );
        }


    


        // Assign permissions to roles

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(array_keys($permissions));
        }

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permissions));
        }

        // HR Manager role permissions
        $hrManagerRole = Role::where('name', 'hr_manager')->first();
        if ($hrManagerRole) {
            $hrManagerRole->givePermissionTo([
                'announcements.view',
                'announcements.view.all',
                'announcements.create',
                'announcements.edit',
                'announcements.delete',
                'announcements.publish',
                'announcements.pin',
                'announcements.target.all',
                'announcements.target.departments',
                'announcements.target.teams',
                'announcements.target.users',
                'announcements.send.email',
                'announcements.send.notification',
                'announcements.require.acknowledgment',
                'announcements.upload.attachment',
                'announcements.reports.view',
                'announcements.reports.read_tracking',
                'announcements.reports.acknowledgment'
            ]);
        }

        // Manager role permissions
        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo([
                'announcements.view',
                'announcements.view.all',
                'announcements.create',
                'announcements.edit',
                'announcements.delete',
                'announcements.publish',
                'announcements.target.departments',
                'announcements.target.teams',
                'announcements.send.notification',
                'announcements.upload.attachment',
                'announcements.reports.view',
                'announcements.reports.read_tracking'
            ]);
        }

        // Employee role permissions
        $employeeRole = Role::where('name', 'employee')->first();
        if ($employeeRole) {
            $employeeRole->givePermissionTo([
                'announcements.view',
                'announcements.acknowledge'
            ]);
        }
    }
}