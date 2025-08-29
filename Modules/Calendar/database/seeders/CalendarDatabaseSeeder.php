<?php

namespace Modules\Calendar\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Calendar\app\Models\Event;

class CalendarDatabaseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Create some sample events only if users exist
    $users = User::limit(5)->get();

    if ($users->isNotEmpty()) {
      // Create basic events without related entities (10 events)
      $basicEvents = Event::factory(10)->create();

      // Attach random attendees to basic events
      $basicEvents->each(function ($event) use ($users) {
        $attendees = $users->random(rand(1, min(3, $users->count())));
        $event->attendees()->attach($attendees->pluck('id'));
      });

      // Create specific event types with demonstration scenarios

      // 1. Meeting events (5 total)
      $meetingEvents = Event::factory(5)->meeting()->create();
      $meetingEvents->each(function ($event) use ($users) {
        $attendees = $users->random(rand(2, min(4, $users->count())));
        $event->attendees()->attach($attendees->pluck('id'));
      });

      // 2. All-day events (3 total)
      $allDayEvents = Event::factory(3)->allDay()->create();
      $allDayEvents->each(function ($event) use ($users) {
        $attendees = $users->random(rand(1, min(2, $users->count())));
        $event->attendees()->attach($attendees->pluck('id'));
      });

      // 3. Client appointments (if Client model exists)
      if (class_exists(\Modules\FieldManager\app\Models\Client::class)) {
        $clientEvents = Event::factory(4)->clientAppointment()->create();
        $clientEvents->each(function ($event) use ($users) {
          // Usually 1-2 attendees for client appointments
          $attendees = $users->random(rand(1, min(2, $users->count())));
          $event->attendees()->attach($attendees->pluck('id'));
        });
      }

      // 4. Company events (if CRMCore Company model exists)
      if (class_exists(\Modules\CRMCore\app\Models\Company::class)) {
        $companyEvents = Event::factory(3)->companyEvent()->create();
        $companyEvents->each(function ($event) use ($users) {
          // Company events usually have more attendees
          $attendees = $users->random(rand(2, $users->count()));
          $event->attendees()->attach($attendees->pluck('id'));
        });
      }

      // 5. Contact meetings (if CRMCore Contact model exists)
      if (class_exists(\Modules\CRMCore\app\Models\Contact::class)) {
        $contactEvents = Event::factory(3)->contactMeeting()->create();
        $contactEvents->each(function ($event) use ($users) {
          $attendees = $users->random(rand(1, min(3, $users->count())));
          $event->attendees()->attach($attendees->pluck('id'));
        });
      }

      // 6. Project meetings (for future PM integration - these won't have related entities yet)
      $projectEvents = Event::factory(2)->projectMeeting()->create();
      $projectEvents->each(function ($event) use ($users) {
        $attendees = $users->random(rand(2, min(4, $users->count())));
        $event->attendees()->attach($attendees->pluck('id'));
      });

      // 7. Create some events with specific demo scenarios

      // Demo: Training event with all users
      if ($users->count() > 0) {
        $trainingEvent = Event::factory()->create([
          'title' => 'Monthly Team Training',
          'description' => 'Comprehensive training session for all team members on new processes and tools.',
          'event_type' => \App\Enums\EventType::TRAINING,
          'start' => now()->addDays(7)->setTime(9, 0),
          'end' => now()->addDays(7)->setTime(17, 0),
          'location' => 'Main Conference Room',
          'color' => '#28a745',
        ]);
        $trainingEvent->attendees()->attach($users->pluck('id'));
      }

      // Demo: Company holiday event
      $holidayEvent = Event::factory()->create([
        'title' => 'Company Holiday - Independence Day',
        'description' => 'Office closed for Independence Day celebration.',
        'event_type' => \App\Enums\EventType::HOLIDAY,
        'start' => now()->addDays(30)->setTime(0, 0),
        'end' => null,
        'all_day' => true,
        'color' => '#dc3545',
      ]);

      // Demo: Deadline event
      $deadlineEvent = Event::factory()->create([
        'title' => 'Project Milestone Deadline',
        'description' => 'Final submission deadline for Q3 project deliverables.',
        'event_type' => \App\Enums\EventType::DEADLINE,
        'start' => now()->addDays(14)->setTime(17, 0),
        'end' => null,
        'color' => '#ffc107',
      ]);
      $deadlineEvent->attendees()->attach($users->take(2)->pluck('id'));

      $this->command->info('Calendar demo data created successfully:');
      $this->command->info('- ' . Event::count() . ' total events created');
      $this->command->info('- Events with Client relationships: ' . Event::where('related_type', 'Modules\FieldManager\app\Models\Client')->count());
      $this->command->info('- Events with Company relationships: ' . Event::where('related_type', 'Modules\CRMCore\app\Models\Company')->count());
      $this->command->info('- Events with Contact relationships: ' . Event::where('related_type', 'Modules\CRMCore\app\Models\Contact')->count());
    }
  }
}
