<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\HRCore\app\Models\Designation;
use Modules\HRCore\app\Models\Shift;
use Modules\HRCore\app\Models\Team;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating demo user accounts...');

        // Create demo users for all roles
        $this->createDemoUsers();

        $this->command->info('Demo user accounts created successfully!');
    }

    /**
     * Create demo users for all roles
     */
    private function createDemoUsers(): void
    {
        $this->command->info('Creating demo users...');

        $shift = Shift::where('name', 'Default Shift')->first();
        $defaultTeam = Team::first();

        // Get designations
        $adminDesignation = Designation::where('name', 'Admin Manager')->first();
        $hrDesignation = Designation::where('name', 'HR Manager')->first();
        $hrExecutiveDesignation = Designation::where('name', 'HR Executive')->first();
        $financeDesignation = Designation::where('name', 'Finance Manager')->first();
        $financeExecutiveDesignation = Designation::where('name', 'Finance Executive')->first();
        $salesDesignation = Designation::where('name', 'Sales Manager')->first();
        $salesExecutiveDesignation = Designation::where('name', 'Sales Executive')->first();
        $projectManagerDesignation = Designation::where('name', 'Operations Manager')->first();
        $inventoryManagerDesignation = Designation::where('name', 'Operations Manager')->first();
        $teamLeaderDesignation = Designation::where('name', 'Sales Manager')->first();
        $employeeDesignation = Designation::where('name', 'Sales Associate')->first();
        $fieldEmployeeDesignation = Designation::where('name', 'Sales Representative')->first();

        $defaultPassword = bcrypt('123456');

        // Create Super Admin
        $superAdmin = User::factory()->create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@demo.com',
            'phone' => '1000000001',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-001',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $adminDesignation->id,
            'gender' => 'male',
            'date_of_joining' => now()->subYears(8)->subDays(rand(0, 365)),
        ]);
        $superAdmin->assignRole('super_admin');
        $this->command->info('Super Admin created: superadmin@demo.com');

        // Create Admin
        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@demo.com',
            'phone' => '1000000002',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-002',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $adminDesignation->id,
            'reporting_to_id' => $superAdmin->id,
            'gender' => 'female',
            'date_of_joining' => now()->subYears(6)->subDays(rand(0, 365)),
        ]);
        $admin->assignRole('admin');
        $this->command->info('Admin created: admin@demo.com');

        // Create HR Manager
        $hrManager = User::factory()->create([
            'first_name' => 'HR',
            'last_name' => 'Manager',
            'email' => 'hr.manager@demo.com',
            'phone' => '1000000003',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-003',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $hrDesignation->id,
            'reporting_to_id' => $admin->id,
            'gender' => 'male',
            'date_of_joining' => now()->subYears(5)->subDays(rand(0, 365)),
        ]);
        $hrManager->assignRole('hr_manager');
        $this->command->info('HR Manager created: hr.manager@demo.com');

        // Create HR Executive
        $hrExecutive = User::factory()->create([
            'first_name' => 'HR',
            'last_name' => 'Executive',
            'email' => 'hr.executive@demo.com',
            'phone' => '1000000004',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-004',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $hrExecutiveDesignation->id,
            'reporting_to_id' => $hrManager->id,
            'gender' => rand(0, 1) ? 'male' : 'female',
            'date_of_joining' => now()->subYears(rand(1, 4))->subDays(rand(0, 365)),
        ]);
        $hrExecutive->assignRole('hr_executive');
        $this->command->info('HR Executive created: hr.executive@demo.com');

        // Create Accounting Manager
        $accountingManager = User::factory()->create([
            'first_name' => 'Accounting',
            'last_name' => 'Manager',
            'email' => 'accounting.manager@demo.com',
            'phone' => '1000000005',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-005',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $financeDesignation->id,
            'reporting_to_id' => $admin->id,
        ]);
        $accountingManager->assignRole('accounting_manager');
        $this->command->info('Accounting Manager created: accounting.manager@demo.com');

        // Create Accounting Executive
        $accountingExecutive = User::factory()->create([
            'first_name' => 'Accounting',
            'last_name' => 'Executive',
            'email' => 'accounting.executive@demo.com',
            'phone' => '1000000006',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-006',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $financeExecutiveDesignation->id,
            'reporting_to_id' => $accountingManager->id,
        ]);
        $accountingExecutive->assignRole('accounting_executive');
        $this->command->info('Accounting Executive created: accounting.executive@demo.com');

        // Create CRM Manager
        $crmManager = User::factory()->create([
            'first_name' => 'CRM',
            'last_name' => 'Manager',
            'email' => 'crm.manager@demo.com',
            'phone' => '1000000007',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-007',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $salesDesignation->id,
            'reporting_to_id' => $admin->id,
        ]);
        $crmManager->assignRole('crm_manager');
        $this->command->info('CRM Manager created: crm.manager@demo.com');

        // Create Project Manager
        $projectManager = User::factory()->create([
            'first_name' => 'Project',
            'last_name' => 'Manager',
            'email' => 'project.manager@demo.com',
            'phone' => '1000000008',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-008',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $projectManagerDesignation->id,
            'reporting_to_id' => $admin->id,
        ]);
        $projectManager->assignRole('project_manager');
        $this->command->info('Project Manager created: project.manager@demo.com');

        // Create Inventory Manager
        $inventoryManager = User::factory()->create([
            'first_name' => 'Inventory',
            'last_name' => 'Manager',
            'email' => 'inventory.manager@demo.com',
            'phone' => '1000000009',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-009',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $inventoryManagerDesignation->id,
            'reporting_to_id' => $admin->id,
        ]);
        $inventoryManager->assignRole('inventory_manager');
        $this->command->info('Inventory Manager created: inventory.manager@demo.com');

        // Create Sales Manager
        $salesManager = User::factory()->create([
            'first_name' => 'Sales',
            'last_name' => 'Manager',
            'email' => 'sales.manager@demo.com',
            'phone' => '1000000010',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-010',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $salesDesignation->id,
            'reporting_to_id' => $crmManager->id,
        ]);
        $salesManager->assignRole('sales_manager');
        $this->command->info('Sales Manager created: sales.manager@demo.com');

        // Create Sales Executive
        $salesExecutive = User::factory()->create([
            'first_name' => 'Sales',
            'last_name' => 'Executive',
            'email' => 'sales.executive@demo.com',
            'phone' => '1000000011',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-011',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $salesExecutiveDesignation->id,
            'reporting_to_id' => $salesManager->id,
        ]);
        $salesExecutive->assignRole('sales_executive');
        $this->command->info('Sales Executive created: sales.executive@demo.com');

        // Create Team Leader
        $teamLeader = User::factory()->create([
            'first_name' => 'Team',
            'last_name' => 'Leader',
            'email' => 'team.leader@demo.com',
            'phone' => '1000000012',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-012',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $teamLeaderDesignation->id,
            'reporting_to_id' => $salesManager->id,
        ]);
        $teamLeader->assignRole('team_leader');
        $this->command->info('Team Leader created: team.leader@demo.com');

        // Create Employee
        $employee = User::factory()->create([
            'first_name' => 'Regular',
            'last_name' => 'Employee',
            'email' => 'employee@demo.com',
            'phone' => '1000000013',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-013',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $employeeDesignation->id,
            'reporting_to_id' => $teamLeader->id,
            'gender' => 'female',
            'date_of_joining' => now()->subYears(2)->subDays(rand(0, 365)),
        ]);
        $employee->assignRole('employee');
        $this->command->info('Employee created: employee@demo.com');

        // Create Field Employee
        $fieldEmployee = User::factory()->create([
            'first_name' => 'Field',
            'last_name' => 'Employee',
            'email' => 'field.employee@demo.com',
            'phone' => '1000000014',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'EMP-014',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $defaultTeam->id,
            'designation_id' => $fieldEmployeeDesignation->id,
            'reporting_to_id' => $teamLeader->id,
            'gender' => 'male',
            'date_of_joining' => now()->subYears(1)->subDays(rand(0, 365)),
        ]);
        $fieldEmployee->assignRole('field_employee');
        $this->command->info('Field Employee created: field.employee@demo.com');

        // Create Client
        $client = User::factory()->create([
            'first_name' => 'Demo',
            'last_name' => 'Client',
            'email' => 'client@demo.com',
            'phone' => '1000000015',
            'phone_verified_at' => now(),
            'password' => $defaultPassword,
            'code' => 'CLIENT-001',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        $client->assignRole('client');
        $this->command->info('Client created: client@demo.com');

        $this->command->info('All demo users created with password: 123456');
    }
}
