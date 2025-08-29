<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\HRCore\app\Models\Designation;
use Modules\HRCore\app\Models\Shift;
use Modules\HRCore\app\Models\Team;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds for production environment
     */
    public function run(): void
    {
        $this->command->info('Creating production super admin account...');

        // Get required data
        $shift = Shift::where('code', 'SH-001')->first();
        $team = Team::where('code', 'TM-001')->first();
        $designation = Designation::where('code', 'DES-001')->first();

        if (!$shift || !$team || !$designation) {
            $this->command->error('Default data not found. Please run HRCore production seeder first.');
            return;
        }

        // Create Super Admin account
        $superAdmin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => config('app.super_admin_email', 'admin@example.com'),
            'phone' => '1000000001',
            'phone_verified_at' => now(),
            'password' => bcrypt('123456'),
            'code' => 'EMP-001',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $team->id,
            'designation_id' => $designation->id,
            'gender' => 'male',
            'date_of_joining' => now(),
        ]);

        $superAdmin->assignRole('super_admin');

        $this->command->info('Production super admin created successfully!');
        $this->command->info('Email: ' . $superAdmin->email);
        $this->command->info('Password: 123456');
        $this->command->warn('Please change the password immediately after login!');
    }
}