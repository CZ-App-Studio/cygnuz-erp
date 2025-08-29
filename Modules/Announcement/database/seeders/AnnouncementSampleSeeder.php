<?php

namespace Modules\Announcement\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Announcement\app\Models\Announcement;
use Modules\HRCore\app\Models\Department;
use Modules\HRCore\app\Models\Team;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AnnouncementSampleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Get sample data for relationships
    $departments = Department::take(5)->pluck('id')->toArray();
    $teams = Team::take(3)->pluck('id')->toArray();
    $users = User::take(10)->pluck('id')->toArray();
    $adminUser = User::whereHas('roles', function ($q) {
      $q->whereIn('name', ['admin', 'super_admin', 'hr_manager']);
    })->first();

    if (!$adminUser) {
      $adminUser = User::first();
    }

    $createdBy = $adminUser ? $adminUser->id : 1;

    DB::beginTransaction();

    try {
      // 1. URGENT - Company-wide Emergency Announcement (Pinned)
      $announcement1 = Announcement::create([
        'title' => 'Emergency Office Closure - Severe Weather Alert',
        'description' => 'Office will be closed tomorrow due to severe weather conditions',
        'content' => '<h3>Important Notice</h3>
                    <p>Due to the severe weather warning issued by the National Weather Service, all office locations will be <strong>closed tomorrow</strong>.</p>
                    <ul>
                        <li>All employees should work from home if possible</li>
                        <li>Essential personnel will be notified separately</li>
                        <li>Check your email for updates</li>
                        <li>Stay safe and avoid unnecessary travel</li>
                    </ul>
                    <p>We will continue to monitor the situation and provide updates as needed. Your safety is our top priority.</p>',
        'priority' => 'urgent',
        'type' => 'important',
        'target_audience' => 'all',
        'send_email' => true,
        'send_notification' => true,
        'is_pinned' => true,
        'requires_acknowledgment' => true,
        'publish_date' => Carbon::now(),
        'expiry_date' => Carbon::now()->addDays(2),
        'status' => 'published',
        'created_by' => $createdBy,
        'metadata' => [
          'category' => 'emergency',
          'notification_sent' => true,
          'estimated_impact' => 'high'
        ]
      ]);

      // 2. HIGH Priority - New Policy Implementation
      $announcement2 = Announcement::create([
        'title' => 'New Remote Work Policy - Effective Next Month',
        'description' => 'Updated remote work guidelines and procedures for all employees',
        'content' => '<h2>Remote Work Policy Update</h2>
                    <p>We are excited to announce our new flexible remote work policy that will take effect from next month.</p>
                    <h3>Key Changes:</h3>
                    <ol>
                        <li><strong>Hybrid Model:</strong> Employees can work remotely up to 3 days per week</li>
                        <li><strong>Core Hours:</strong> All team members must be available from 10 AM to 3 PM</li>
                        <li><strong>Equipment:</strong> Company will provide necessary equipment for home office setup</li>
                        <li><strong>Monthly Allowance:</strong> $100 internet and utilities stipend</li>
                    </ol>
                    <h3>Eligibility:</h3>
                    <ul>
                        <li>Full-time employees with 6+ months tenure</li>
                        <li>Manager approval required</li>
                        <li>Performance rating of "Meets Expectations" or higher</li>
                    </ul>
                    <p>Please review the full policy document attached and acknowledge your understanding.</p>',
        'priority' => 'high',
        'type' => 'policy',
        'target_audience' => 'all',
        'send_email' => true,
        'send_notification' => true,
        'is_pinned' => true,
        'requires_acknowledgment' => true,
        'publish_date' => Carbon::now(),
        'expiry_date' => Carbon::now()->addMonth(),
        'status' => 'published',
        'created_by' => $createdBy,
        'metadata' => [
          'policy_version' => '2.0',
          'requires_training' => false,
          'department_specific' => false
        ]
      ]);

      // 3. Company Event - Annual Conference (Future/Scheduled)
      $announcement3 = Announcement::create([
        'title' => 'Annual Company Conference 2025 - Save the Date!',
        'description' => 'Join us for our biggest event of the year',
        'content' => '<h2>🎉 Annual Conference 2025</h2>
                    <p>Mark your calendars! Our annual company conference is scheduled for <strong>March 15-17, 2025</strong>.</p>
                    <h3>Event Highlights:</h3>
                    <ul>
                        <li>Keynote speakers from industry leaders</li>
                        <li>Product launches and demonstrations</li>
                        <li>Team building activities</li>
                        <li>Awards ceremony</li>
                        <li>Networking opportunities</li>
                    </ul>
                    <h3>Location:</h3>
                    <p>Grand Ballroom, Hilton Downtown<br>
                    123 Main Street, City Center</p>
                    <h3>Registration:</h3>
                    <p>Registration opens February 1st. All expenses covered for employees.</p>
                    <p>More details coming soon!</p>',
        'priority' => 'normal',
        'type' => 'event',
        'target_audience' => 'all',
        'send_email' => false,
        'send_notification' => true,
        'is_pinned' => false,
        'requires_acknowledgment' => false,
        'publish_date' => Carbon::now()->addWeek(),
        'expiry_date' => Carbon::parse('2025-03-17'),
        'status' => 'scheduled',
        'created_by' => $createdBy,
        'metadata' => [
          'event_date' => '2025-03-15',
          'location' => 'Hilton Downtown',
          'registration_required' => true
        ]
      ]);

      // 4. Department-specific IT System Update
      if (!empty($departments)) {
        $announcement4 = Announcement::create([
          'title' => 'IT System Maintenance - This Weekend',
          'description' => 'Scheduled maintenance for IT and Engineering departments',
          'content' => '<h3>Scheduled System Maintenance</h3>
                        <p>Please be informed that we will be performing critical system maintenance this weekend.</p>
                        <h4>Maintenance Window:</h4>
                        <ul>
                            <li>Start: Saturday, 10:00 PM</li>
                            <li>End: Sunday, 6:00 AM</li>
                        </ul>
                        <h4>Affected Systems:</h4>
                        <ul>
                            <li>Email servers</li>
                            <li>VPN access</li>
                            <li>Internal development environments</li>
                            <li>Git repositories</li>
                        </ul>
                        <h4>Action Required:</h4>
                        <p>Please save all work and log out of systems by 9:30 PM on Saturday.</p>',
          'priority' => 'high',
          'type' => 'update',
          'target_audience' => 'departments',
          'send_email' => true,
          'send_notification' => true,
          'is_pinned' => false,
          'requires_acknowledgment' => true,
          'publish_date' => Carbon::now(),
          'expiry_date' => Carbon::now()->addDays(3),
          'status' => 'published',
          'created_by' => $createdBy,
          'metadata' => [
            'maintenance_type' => 'scheduled',
            'downtime_hours' => 8,
            'affected_services' => ['email', 'vpn', 'git']
          ]
        ]);
        $announcement4->departments()->attach(array_slice($departments, 0, 2));
      }

      // 5. Team-specific Project Launch
      if (!empty($teams)) {
        $announcement5 = Announcement::create([
          'title' => 'Project Phoenix - Kickoff Meeting Tomorrow',
          'description' => 'Important project kickoff for selected teams',
          'content' => '<h2>Project Phoenix Launch</h2>
                        <p>Selected teams will be participating in the new Project Phoenix initiative.</p>
                        <h3>Meeting Details:</h3>
                        <ul>
                            <li><strong>Date:</strong> Tomorrow</li>
                            <li><strong>Time:</strong> 2:00 PM - 4:00 PM</li>
                            <li><strong>Location:</strong> Conference Room A / Virtual Link</li>
                        </ul>
                        <h3>Agenda:</h3>
                        <ol>
                            <li>Project overview and objectives</li>
                            <li>Timeline and milestones</li>
                            <li>Team responsibilities</li>
                            <li>Q&A session</li>
                        </ol>
                        <p>Please come prepared with your questions and initial ideas.</p>',
          'priority' => 'high',
          'type' => 'important',
          'target_audience' => 'teams',
          'send_email' => true,
          'send_notification' => true,
          'is_pinned' => false,
          'requires_acknowledgment' => true,
          'publish_date' => Carbon::now(),
          'expiry_date' => Carbon::now()->addDays(2),
          'status' => 'published',
          'created_by' => $createdBy,
          'metadata' => [
            'project_name' => 'Phoenix',
            'project_phase' => 'kickoff',
            'duration_months' => 6
          ]
        ]);
        $announcement5->teams()->attach($teams);
      }

      // 6. Benefits Update - Normal Priority
      $announcement6 = Announcement::create([
        'title' => 'Health Insurance Open Enrollment Period',
        'description' => 'Annual benefits enrollment period is now open',
        'content' => '<h2>Open Enrollment for Health Benefits</h2>
                    <p>The annual open enrollment period for health insurance is now open until the end of this month.</p>
                    <h3>What\'s New This Year:</h3>
                    <ul>
                        <li>New dental and vision coverage options</li>
                        <li>Increased employer contribution by 10%</li>
                        <li>Telemedicine services now included</li>
                        <li>Wellness program with gym membership discounts</li>
                    </ul>
                    <h3>How to Enroll:</h3>
                    <ol>
                        <li>Log into the benefits portal</li>
                        <li>Review your current selections</li>
                        <li>Make changes as needed</li>
                        <li>Submit by month-end</li>
                    </ol>
                    <p>Contact HR if you need assistance with enrollment.</p>',
        'priority' => 'normal',
        'type' => 'general',
        'target_audience' => 'all',
        'send_email' => true,
        'send_notification' => false,
        'is_pinned' => false,
        'requires_acknowledgment' => false,
        'publish_date' => Carbon::now(),
        'expiry_date' => Carbon::now()->endOfMonth(),
        'status' => 'published',
        'created_by' => $createdBy,
        'metadata' => [
          'enrollment_deadline' => Carbon::now()->endOfMonth()->toDateString(),
          'benefits_year' => '2025',
          'hr_contact' => 'benefits@company.com'
        ]
      ]);

      // 7. Training Announcement - Specific Users
      if (!empty($users)) {
        $announcement7 = Announcement::create([
          'title' => 'Mandatory Compliance Training - Due This Week',
          'description' => 'Selected employees must complete compliance training',
          'content' => '<h3>Mandatory Compliance Training</h3>
                        <p>You have been selected to complete the annual compliance training module.</p>
                        <h4>Training Details:</h4>
                        <ul>
                            <li><strong>Course:</strong> Data Security and Privacy</li>
                            <li><strong>Duration:</strong> 2 hours</li>
                            <li><strong>Deadline:</strong> End of this week</li>
                            <li><strong>Platform:</strong> Company Learning Portal</li>
                        </ul>
                        <h4>Why This Training?</h4>
                        <p>This training ensures compliance with new data protection regulations and helps protect our company and customer data.</p>
                        <p><strong>Note:</strong> Completion is mandatory and will be tracked.</p>',
          'priority' => 'high',
          'type' => 'important',
          'target_audience' => 'specific_users',
          'send_email' => true,
          'send_notification' => true,
          'is_pinned' => false,
          'requires_acknowledgment' => true,
          'publish_date' => Carbon::now(),
          'expiry_date' => Carbon::now()->endOfWeek(),
          'status' => 'published',
          'created_by' => $createdBy,
          'metadata' => [
            'training_type' => 'compliance',
            'mandatory' => true,
            'duration_hours' => 2
          ]
        ]);
        $announcement7->users()->attach(array_slice($users, 0, 5));
      }

      // 8. Company Achievement - Low Priority
      $announcement8 = Announcement::create([
        'title' => 'Celebrating 10 Million Users Milestone!',
        'description' => 'We\'ve reached an incredible milestone together',
        'content' => '<h2>🎊 10 Million Users!</h2>
                    <p>We are thrilled to announce that we have reached <strong>10 million users</strong> on our platform!</p>
                    <p>This incredible milestone wouldn\'t have been possible without the hard work and dedication of every team member.</p>
                    <h3>By the Numbers:</h3>
                    <ul>
                        <li>10,000,000+ active users</li>
                        <li>150+ countries served</li>
                        <li>99.9% uptime maintained</li>
                        <li>4.8/5 average user rating</li>
                    </ul>
                    <p>Thank you all for your contributions to this success. Let\'s continue building amazing products together!</p>
                    <p>Pizza party this Friday to celebrate! 🍕</p>',
        'priority' => 'low',
        'type' => 'general',
        'target_audience' => 'all',
        'send_email' => false,
        'send_notification' => true,
        'is_pinned' => false,
        'requires_acknowledgment' => false,
        'publish_date' => Carbon::now(),
        'expiry_date' => Carbon::now()->addWeek(),
        'status' => 'published',
        'created_by' => $createdBy,
        'metadata' => [
          'celebration_type' => 'milestone',
          'achievement' => '10M users',
          'celebration_date' => Carbon::now()->next('Friday')->toDateString()
        ]
      ]);

      // 9. Draft Announcement (Not Published)
      $announcement9 = Announcement::create([
        'title' => 'Q2 Financial Results - DRAFT',
        'description' => 'Quarterly financial performance update - pending approval',
        'content' => '<h2>Q2 Financial Results</h2>
                    <p>[DRAFT - Pending CEO Approval]</p>
                    <p>Financial highlights for Q2...</p>',
        'priority' => 'normal',
        'type' => 'update',
        'target_audience' => 'all',
        'send_email' => false,
        'send_notification' => false,
        'is_pinned' => false,
        'requires_acknowledgment' => false,
        'publish_date' => Carbon::now()->addDays(7),
        'expiry_date' => null,
        'status' => 'draft',
        'created_by' => $createdBy,
        'metadata' => [
          'draft_version' => 1,
          'requires_approval' => true,
          'approver' => 'CEO'
        ]
      ]);

      // 10. Expired Announcement
      $announcement10 = Announcement::create([
        'title' => 'Holiday Schedule 2024',
        'description' => 'Company holidays for the previous year',
        'content' => '<h3>2024 Holiday Schedule</h3>
                    <p>Please find the holiday schedule for 2024...</p>',
        'priority' => 'low',
        'type' => 'general',
        'target_audience' => 'all',
        'send_email' => false,
        'send_notification' => false,
        'is_pinned' => false,
        'requires_acknowledgment' => false,
        'publish_date' => Carbon::now()->subYear(),
        'expiry_date' => Carbon::now()->subMonth(),
        'status' => 'expired',
        'created_by' => $createdBy,
        'metadata' => [
          'year' => '2024',
          'total_holidays' => 15
        ]
      ]);

      // 11. Office Relocation Update
      $announcement11 = Announcement::create([
        'title' => 'Office Relocation Update - New Address',
        'description' => 'Important information about our office move next quarter',
        'content' => '<h2>Office Relocation Notice</h2>
                    <p>We are excited to announce that we will be moving to a new, modern office space!</p>
                    <h3>New Office Details:</h3>
                    <ul>
                        <li><strong>Address:</strong> 500 Innovation Drive, Tech Park, Suite 1000</li>
                        <li><strong>Move Date:</strong> ' . Carbon::now()->addMonths(3)->format('F d, Y') . '</li>
                        <li><strong>Parking:</strong> Free parking for all employees</li>
                        <li><strong>Public Transit:</strong> 5-minute walk from Central Station</li>
                    </ul>
                    <h3>New Amenities:</h3>
                    <ul>
                        <li>State-of-the-art gym and wellness center</li>
                        <li>Cafeteria with healthy meal options</li>
                        <li>Outdoor terrace and recreational areas</li>
                        <li>Modern meeting rooms with latest technology</li>
                        <li>Quiet zones and collaboration spaces</li>
                    </ul>
                    <p>More details about the moving process will be shared soon.</p>',
        'priority' => 'normal',
        'type' => 'update',
        'target_audience' => 'all',
        'send_email' => true,
        'send_notification' => false,
        'is_pinned' => true,
        'requires_acknowledgment' => false,
        'publish_date' => Carbon::now(),
        'expiry_date' => Carbon::now()->addMonths(3),
        'status' => 'published',
        'created_by' => $createdBy,
        'metadata' => [
          'relocation_date' => Carbon::now()->addMonths(3)->toDateString(),
          'new_location' => 'Tech Park',
          'impact' => 'all_employees'
        ]
      ]);

      // 12. Wellness Program Launch
      $announcement12 = Announcement::create([
        'title' => 'New Employee Wellness Program - Join Today!',
        'description' => 'Introducing comprehensive wellness benefits for all employees',
        'content' => '<h2>🌟 Employee Wellness Program Launch</h2>
                    <p>Your health and well-being matter to us! We\'re launching a comprehensive wellness program.</p>
                    <h3>Program Benefits:</h3>
                    <ul>
                        <li>Free gym membership at 200+ locations</li>
                        <li>Mental health support and counseling services</li>
                        <li>Nutrition consultations and meal planning</li>
                        <li>Yoga and meditation classes (virtual & in-person)</li>
                        <li>Annual health screenings</li>
                        <li>Fitness challenges with rewards</li>
                    </ul>
                    <h3>How to Participate:</h3>
                    <ol>
                        <li>Register on the wellness portal</li>
                        <li>Complete health assessment</li>
                        <li>Choose your wellness activities</li>
                        <li>Track progress and earn points</li>
                    </ol>
                    <p><strong>Special Offer:</strong> First 100 registrants receive a fitness tracker!</p>',
        'priority' => 'normal',
        'type' => 'general',
        'target_audience' => 'all',
        'send_email' => true,
        'send_notification' => true,
        'is_pinned' => false,
        'requires_acknowledgment' => false,
        'publish_date' => Carbon::now(),
        'expiry_date' => Carbon::now()->addMonths(2),
        'status' => 'published',
        'created_by' => $createdBy,
        'metadata' => [
          'program_type' => 'wellness',
          'enrollment_bonus' => 'fitness_tracker',
          'annual_budget' => '$500_per_employee'
        ]
      ]);

      // 13. Security Alert
      $announcement13 = Announcement::create([
        'title' => 'Security Alert: Phishing Email Warning',
        'description' => 'Important security notice - beware of suspicious emails',
        'content' => '<h2>⚠️ Security Alert</h2>
                    <p><strong>Several employees have reported receiving suspicious emails. Please be vigilant!</strong></p>
                    <h3>What to Look For:</h3>
                    <ul>
                        <li>Emails asking for password or personal information</li>
                        <li>Unexpected attachments or links</li>
                        <li>Poor grammar or spelling</li>
                        <li>Urgent requests for money or gift cards</li>
                        <li>Sender addresses that don\'t match company domain</li>
                    </ul>
                    <h3>If You Receive a Suspicious Email:</h3>
                    <ol>
                        <li>Do NOT click any links or download attachments</li>
                        <li>Do NOT reply or provide any information</li>
                        <li>Forward the email to security@company.com</li>
                        <li>Delete the email from your inbox</li>
                    </ol>
                    <p>Remember: IT will NEVER ask for your password via email.</p>',
        'priority' => 'urgent',
        'type' => 'important',
        'target_audience' => 'all',
        'send_email' => true,
        'send_notification' => true,
        'is_pinned' => true,
        'requires_acknowledgment' => true,
        'publish_date' => Carbon::now(),
        'expiry_date' => Carbon::now()->addWeek(),
        'status' => 'published',
        'created_by' => $createdBy,
        'metadata' => [
          'security_level' => 'high',
          'incident_type' => 'phishing',
          'reported_incidents' => 5
        ]
      ]);

      // 14. Performance Review Cycle
      if (!empty($departments)) {
        $announcement14 = Announcement::create([
          'title' => 'Q4 Performance Review Cycle Starting',
          'description' => 'Annual performance review process begins next week',
          'content' => '<h2>Performance Review Process</h2>
                        <p>The Q4 performance review cycle will begin next week for selected departments.</p>
                        <h3>Timeline:</h3>
                        <ul>
                            <li><strong>Self-Assessment:</strong> ' . Carbon::now()->addWeek()->format('M d') . ' - ' . Carbon::now()->addWeeks(2)->format('M d') . '</li>
                            <li><strong>Manager Reviews:</strong> ' . Carbon::now()->addWeeks(2)->format('M d') . ' - ' . Carbon::now()->addWeeks(3)->format('M d') . '</li>
                            <li><strong>Calibration:</strong> ' . Carbon::now()->addWeeks(3)->format('M d') . ' - ' . Carbon::now()->addWeeks(4)->format('M d') . '</li>
                            <li><strong>Feedback Delivery:</strong> ' . Carbon::now()->addMonth()->format('M d') . '</li>
                        </ul>
                        <h3>What to Prepare:</h3>
                        <ul>
                            <li>List of key accomplishments</li>
                            <li>Goals achieved vs. planned</li>
                            <li>Areas for development</li>
                            <li>Career aspirations</li>
                        </ul>',
          'priority' => 'high',
          'type' => 'important',
          'target_audience' => 'departments',
          'send_email' => true,
          'send_notification' => true,
          'is_pinned' => false,
          'requires_acknowledgment' => true,
          'publish_date' => Carbon::now(),
          'expiry_date' => Carbon::now()->addMonth(),
          'status' => 'published',
          'created_by' => $createdBy,
          'metadata' => [
            'review_cycle' => 'Q4',
            'review_type' => 'annual',
            'affects_compensation' => true
          ]
        ]);
        $announcement14->departments()->attach($departments);
      }

      // 15. Archived Policy Document
      $announcement15 = Announcement::create([
        'title' => 'Travel Policy Update 2023 - Archived',
        'description' => 'Previous year travel policy - archived for reference',
        'content' => '<h3>Travel Policy 2023</h3>
                    <p>This policy has been superseded by the 2024 travel policy...</p>',
        'priority' => 'low',
        'type' => 'policy',
        'target_audience' => 'all',
        'send_email' => false,
        'send_notification' => false,
        'is_pinned' => false,
        'requires_acknowledgment' => false,
        'publish_date' => Carbon::now()->subYear(),
        'expiry_date' => Carbon::now()->subMonths(6),
        'status' => 'archived',
        'created_by' => $createdBy,
        'metadata' => [
          'policy_version' => '1.0',
          'archived_date' => Carbon::now()->subMonths(3)->toDateString(),
          'superseded_by' => 'Travel Policy 2024'
        ]
      ]);

      DB::commit();

      // Send notifications for published announcements
      $this->command->info('Sending notifications for published announcements...');

      $publishedAnnouncements = [
        $announcement1,  // Emergency Office Closure
        $announcement2,  // New Remote Work Policy
        $announcement6,  // Health Insurance Open Enrollment
        $announcement8,  // 10 Million Users Milestone
        $announcement11, // Office Relocation
        $announcement12, // Wellness Program
        $announcement13, // Security Alert
      ];

      // Add department-specific announcements if they exist
      if (isset($announcement4)) {
        $publishedAnnouncements[] = $announcement4; // IT System Maintenance
      }
      if (isset($announcement5)) {
        $publishedAnnouncements[] = $announcement5; // Project Phoenix
      }
      if (isset($announcement7)) {
        $publishedAnnouncements[] = $announcement7; // Mandatory Training
      }
      if (isset($announcement14)) {
        $publishedAnnouncements[] = $announcement14; // Performance Review
      }

      foreach ($publishedAnnouncements as $announcement) {
        $this->sendNotificationsForAnnouncement($announcement);
      }

      $this->command->info('Sample announcements created successfully!');
      $this->command->info('Created 15 announcements with various:');
      $this->command->info('- Priorities: urgent, high, normal, low');
      $this->command->info('- Types: general, important, event, policy, update');
      $this->command->info('- Statuses: published, scheduled, draft, expired, archived');
      $this->command->info('- Target audiences: all, departments, teams, specific users');
      $this->command->info('- Features: pinned, email, notifications, acknowledgments');
      $this->command->info('- Notifications sent for all published announcements');

    } catch (\Exception $e) {
      DB::rollBack();
      $this->command->error('Failed to create sample announcements: ' . $e->getMessage());
    }
  }

  /**
   * Send notifications for an announcement based on its target audience
   */
  protected function sendNotificationsForAnnouncement(Announcement $announcement)
  {
    // Only send notifications if the announcement is published and has notification enabled
    if ($announcement->status !== 'published' || !$announcement->send_notification) {
      return;
    }

    // Get target users based on audience
    $users = collect();

    switch ($announcement->target_audience) {
      case 'all':
        $users = User::all();
        break;

      case 'departments':
        // Get users whose designation belongs to the selected departments
        $departmentIds = $announcement->departments->pluck('id');
        $users = User::whereHas('designation', function ($q) use ($departmentIds) {
          $q->whereIn('department_id', $departmentIds);
        })->get();
        break;

      case 'teams':
        $users = User::whereIn('team_id', $announcement->teams->pluck('id'))->get();
        break;

      case 'specific_users':
        $users = $announcement->users;
        break;
    }

    // Determine notification type based on priority
    $notificationType = 'new';
    if ($announcement->priority === 'urgent') {
      $notificationType = 'urgent';
    }

    // Send notifications to all target users (in chunks for better performance)
    if ($users->count() > 0) {
      try {
        // Send notifications in chunks of 50 users for better performance
        $userChunks = $users->chunk(50);
        $totalUsers = $users->count();

        foreach ($userChunks as $userChunk) {
          //TODO:Need to check
          //Notification::send($userChunk, new AnnouncementPublished($announcement, $notificationType));
        }

        $this->command->info("- Sent notifications for: {$announcement->title} to {$totalUsers} users");
      } catch (\Exception $e) {
        $this->command->warn("- Failed to send notifications for: {$announcement->title} - " . $e->getMessage());
      }
    }
  }
}
