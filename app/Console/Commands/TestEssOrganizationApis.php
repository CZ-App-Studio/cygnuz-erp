<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class TestEssOrganizationApis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ess-apis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and document ESS Organization APIs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=====================================");
        $this->info("ESS APP ORGANIZATION API TESTING");
        $this->info("=====================================\n");

        // Get a test user
        $user = User::first();
        if (!$user) {
            $this->error("No user found for testing. Please create a user first.");
            return 1;
        }

        // Generate token
        $token = auth('api')->login($user);
        $this->info("Test User: " . $user->email);
        $this->info("Token Generated: " . substr($token, 0, 50) . "...\n");

        // Set base URL
        $baseUrl = url('/api/essapp/v1/organization');
        
        // Test each endpoint
        $this->testDepartments($baseUrl, $token);
        $this->testDesignations($baseUrl, $token);
        $this->testShifts($baseUrl, $token);
        $this->testMySchedule($baseUrl, $token);
        $this->testHolidays($baseUrl, $token);
        $this->testTeams($baseUrl, $token);
        
        $this->info("\n=====================================");
        $this->info("API TESTING COMPLETE");
        $this->info("=====================================");
        
        return 0;
    }

    private function testDepartments($baseUrl, $token)
    {
        $this->info("\n### TESTING DEPARTMENTS API ###");
        $this->info("Endpoint: GET $baseUrl/departments");
        
        try {
            $response = Http::withToken($token)
                ->get("$baseUrl/departments");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Status: " . $response->status());
                $this->info("Response: " . ($data['success'] ? 'Success' : 'Failed'));
                $this->info("Message: " . $data['message']);
                
                if (isset($data['data']) && count($data['data']) > 0) {
                    $this->info("Total Departments: " . count($data['data']));
                    $this->info("\nSample Department:");
                    $sample = $data['data'][0];
                    $this->info("  - ID: " . $sample['id']);
                    $this->info("  - Name: " . $sample['name']);
                    $this->info("  - Code: " . ($sample['code'] ?? 'N/A'));
                    $this->info("  - Parent Department: " . ($sample['parent_department']['name'] ?? 'None'));
                    $this->info("  - Head of Department: " . ($sample['head_of_department']['name'] ?? 'None'));
                    $this->info("  - Employee Count: " . $sample['employee_count']);
                }
            } else {
                $this->error("❌ Failed: " . $response->status());
                $this->error("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function testDesignations($baseUrl, $token)
    {
        $this->info("\n### TESTING DESIGNATIONS API ###");
        $this->info("Endpoint: GET $baseUrl/designations");
        
        try {
            $response = Http::withToken($token)
                ->get("$baseUrl/designations");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Status: " . $response->status());
                $this->info("Response: " . ($data['success'] ? 'Success' : 'Failed'));
                $this->info("Message: " . $data['message']);
                
                if (isset($data['data']) && count($data['data']) > 0) {
                    $this->info("Total Designations: " . count($data['data']));
                    $this->info("\nSample Designation:");
                    $sample = $data['data'][0];
                    $this->info("  - ID: " . $sample['id']);
                    $this->info("  - Name: " . $sample['name']);
                    $this->info("  - Code: " . ($sample['code'] ?? 'N/A'));
                    $this->info("  - Department: " . ($sample['department']['name'] ?? 'None'));
                    $this->info("  - Level: " . ($sample['level'] ?? 'N/A'));
                }
            } else {
                $this->error("❌ Failed: " . $response->status());
                $this->error("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function testShifts($baseUrl, $token)
    {
        $this->info("\n### TESTING SHIFTS API ###");
        $this->info("Endpoint: GET $baseUrl/shifts");
        
        try {
            $response = Http::withToken($token)
                ->get("$baseUrl/shifts");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Status: " . $response->status());
                $this->info("Response: " . ($data['success'] ? 'Success' : 'Failed'));
                $this->info("Message: " . $data['message']);
                
                if (isset($data['data']) && count($data['data']) > 0) {
                    $this->info("Total Shifts: " . count($data['data']));
                    $this->info("\nSample Shift:");
                    $sample = $data['data'][0];
                    $this->info("  - ID: " . $sample['id']);
                    $this->info("  - Name: " . $sample['name']);
                    $this->info("  - Code: " . ($sample['code'] ?? 'N/A'));
                    $this->info("  - Start Time: " . $sample['start_time']);
                    $this->info("  - End Time: " . $sample['end_time']);
                    $this->info("  - Break Duration: " . $sample['break_duration'] . " minutes");
                    $this->info("  - Night Shift: " . ($sample['is_night_shift'] ? 'Yes' : 'No'));
                }
            } else {
                $this->error("❌ Failed: " . $response->status());
                $this->error("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function testMySchedule($baseUrl, $token)
    {
        $this->info("\n### TESTING MY SHIFT SCHEDULE API ###");
        $this->info("Endpoint: GET $baseUrl/shifts/my-schedule");
        
        try {
            $response = Http::withToken($token)
                ->get("$baseUrl/shifts/my-schedule", [
                    'month' => date('n'),
                    'year' => date('Y')
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Status: " . $response->status());
                $this->info("Response: " . ($data['success'] ? 'Success' : 'Failed'));
                $this->info("Message: " . $data['message']);
                
                if (isset($data['data'])) {
                    $this->info("\nCurrent Shift: " . ($data['data']['current_shift']['name'] ?? 'None'));
                    if (isset($data['data']['schedule']) && count($data['data']['schedule']) > 0) {
                        $this->info("Schedule Days: " . count($data['data']['schedule']));
                        $sample = $data['data']['schedule'][0];
                        $this->info("\nSample Schedule Entry:");
                        $this->info("  - Date: " . $sample['date']);
                        $this->info("  - Day: " . $sample['day']);
                        $this->info("  - Shift: " . ($sample['shift']['name'] ?? 'N/A'));
                        $this->info("  - Week Off: " . ($sample['is_week_off'] ? 'Yes' : 'No'));
                        $this->info("  - Holiday: " . ($sample['is_holiday'] ? 'Yes' : 'No'));
                    }
                }
            } else {
                $this->error("❌ Failed: " . $response->status());
                $this->error("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function testHolidays($baseUrl, $token)
    {
        $this->info("\n### TESTING HOLIDAYS API ###");
        $this->info("Endpoint: GET $baseUrl/holidays");
        
        try {
            $response = Http::withToken($token)
                ->get("$baseUrl/holidays", [
                    'year' => date('Y')
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Status: " . $response->status());
                $this->info("Response: " . ($data['success'] ? 'Success' : 'Failed'));
                $this->info("Message: " . $data['message']);
                
                if (isset($data['data']) && count($data['data']) > 0) {
                    $this->info("Total Holidays: " . count($data['data']));
                    $this->info("\nSample Holiday:");
                    $sample = $data['data'][0];
                    $this->info("  - ID: " . $sample['id']);
                    $this->info("  - Name: " . $sample['name']);
                    $this->info("  - Date: " . $sample['date']);
                    $this->info("  - Day: " . $sample['day']);
                    $this->info("  - Type: " . $sample['type']);
                    $this->info("  - Category: " . ($sample['category'] ?? 'N/A'));
                    $this->info("  - Optional: " . ($sample['is_optional'] ? 'Yes' : 'No'));
                    $this->info("  - Half Day: " . ($sample['is_half_day'] ? 'Yes' : 'No'));
                    $this->info("  - Upcoming: " . ($sample['is_upcoming'] ? 'Yes' : 'No'));
                    $this->info("  - Days Remaining: " . $sample['days_remaining']);
                }
            } else {
                $this->error("❌ Failed: " . $response->status());
                $this->error("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function testTeams($baseUrl, $token)
    {
        $this->info("\n### TESTING TEAMS API ###");
        $this->info("Endpoint: GET $baseUrl/teams");
        
        try {
            $response = Http::withToken($token)
                ->get("$baseUrl/teams");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Status: " . $response->status());
                $this->info("Response: " . ($data['success'] ? 'Success' : 'Failed'));
                $this->info("Message: " . $data['message']);
                
                if (isset($data['data']) && count($data['data']) > 0) {
                    $this->info("Total Teams: " . count($data['data']));
                    $this->info("\nSample Team:");
                    $sample = $data['data'][0];
                    $this->info("  - ID: " . $sample['id']);
                    $this->info("  - Name: " . $sample['name']);
                    $this->info("  - Code: " . ($sample['code'] ?? 'N/A'));
                    $this->info("  - Description: " . ($sample['description'] ?? 'N/A'));
                    $this->info("  - Team Lead: " . ($sample['team_lead']['name'] ?? 'None'));
                    $this->info("  - Member Count: " . $sample['member_count']);
                    $this->info("  - Is Member: " . ($sample['is_member'] ? 'Yes' : 'No'));
                    $this->info("  - Is Lead: " . ($sample['is_lead'] ? 'Yes' : 'No'));
                    
                    // Test team members
                    $this->testTeamMembers($baseUrl, $token, $sample['id']);
                }
            } else {
                $this->error("❌ Failed: " . $response->status());
                $this->error("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }

    private function testTeamMembers($baseUrl, $token, $teamId)
    {
        $this->info("\n### TESTING TEAM MEMBERS API ###");
        $this->info("Endpoint: GET $baseUrl/teams/$teamId/members");
        
        try {
            $response = Http::withToken($token)
                ->get("$baseUrl/teams/$teamId/members");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ Status: " . $response->status());
                
                if (isset($data['data']) && count($data['data']) > 0) {
                    $this->info("Total Members: " . count($data['data']));
                    $this->info("\nSample Member:");
                    $sample = $data['data'][0];
                    $this->info("  - ID: " . $sample['id']);
                    $this->info("  - Name: " . $sample['name']);
                    $this->info("  - Email: " . $sample['email']);
                    $this->info("  - Designation: " . ($sample['designation'] ?? 'N/A'));
                    $this->info("  - Department: " . ($sample['department'] ?? 'N/A'));
                    $this->info("  - Is Team Lead: " . ($sample['is_team_lead'] ? 'Yes' : 'No'));
                }
            } else {
                $this->error("❌ Failed: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}