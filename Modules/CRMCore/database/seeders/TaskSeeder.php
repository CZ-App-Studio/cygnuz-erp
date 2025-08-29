<?php

namespace Modules\CRMCore\database\seeders;

use App\Enums\UserAccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;
use Modules\CRMCore\app\Models\Deal;
use Modules\CRMCore\app\Models\Lead;
use Modules\CRMCore\app\Models\Task;
use Modules\CRMCore\app\Models\TaskPriority;
use Modules\CRMCore\app\Models\TaskStatus;

class TaskSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // --- 1. Seed Task Statuses ---
    $this->command->info('Seeding Task Statuses...');
    $taskStatusesData = [
      ['name' => 'To Do',         'color' => '#03a9f4', 'position' => 1, 'is_default' => true, 'is_completed_status' => false],
      ['name' => 'In Progress',   'color' => '#ff9800', 'position' => 2, 'is_default' => false, 'is_completed_status' => false],
      ['name' => 'Waiting for Input', 'color' => '#795548', 'position' => 3, 'is_default' => false, 'is_completed_status' => false],
      ['name' => 'Completed',     'color' => '#4caf50', 'position' => 4, 'is_default' => false, 'is_completed_status' => true],
      ['name' => 'Cancelled',     'color' => '#9e9e9e', 'position' => 5, 'is_default' => false, 'is_completed_status' => true], // Also a final state
    ];
    foreach ($taskStatusesData as $statusData) {
      TaskStatus::firstOrCreate(['name' => $statusData['name']], $statusData);
    }

    // --- 2. Seed Task Priorities ---
    $this->command->info('Seeding Task Priorities...');
    $taskPrioritiesData = [
      ['name' => 'Low',    'color' => '#4caf50', 'level' => 1],
      ['name' => 'Medium', 'color' => '#ffc107', 'level' => 2, 'is_default' => true],
      ['name' => 'High',   'color' => '#ff9800', 'level' => 3],
      ['name' => 'Urgent', 'color' => '#f44336', 'level' => 4],
    ];
    foreach ($taskPrioritiesData as $priorityData) {
      TaskPriority::firstOrCreate(['name' => $priorityData['name']], $priorityData);
    }

    // --- 3. Fetch required data for seeding Tasks ---
    $activeUsers = User::where('status', UserAccountStatus::ACTIVE)->pluck('id');
    $taskStatusIds = TaskStatus::pluck('id');
    $defaultStatusId = TaskStatus::where('is_default', true)->first()?->id ?? $taskStatusIds->first();
    $completedStatusId = TaskStatus::where('is_completed_status', true)->where('name', 'Completed')->first()?->id; // Specific completed
    $taskPriorityIds = TaskPriority::pluck('id');
    $defaultPriorityId = TaskPriority::where('is_default', true)->first()?->id ?? $taskPriorityIds->first();

    $taskableModels = [
      Contact::class => Contact::pluck('id'),
      Company::class => Company::pluck('id'),
      Lead::class    => Lead::pluck('id'),
      Deal::class    => Deal::pluck('id'),
    ];

    if ($activeUsers->isEmpty()) {
      $this->command->warn('No active users found. Some tasks might not be assigned. Please run UserSeeder first.');
    }
    if (collect($taskableModels)->every(fn($collection) => $collection->isEmpty())) {
      $this->command->warn('No relatable entities (Contacts, Companies, etc.) found. Some tasks may not be related.');
    }


    // --- 4. Seed Tasks ---
    $this->command->info('Seeding Tasks...');
    $numberOfTasks = 200; // Create around 200 tasks
    $progressBar = $this->command->getOutput()->createProgressBar($numberOfTasks);

    for ($i = 0; $i < $numberOfTasks; $i++) {
      $taskableType = null;
      $taskableId = null;

      // 70% chance to relate a task to something
      if (fake()->boolean(70)) {
        $availableTaskableTypes = array_filter(array_keys($taskableModels), fn($modelClass) => $taskableModels[$modelClass]->isNotEmpty());
        if (!empty($availableTaskableTypes)) {
          $taskableTypeClass = fake()->randomElement($availableTaskableTypes);
          $taskableId = $taskableModels[$taskableTypeClass]->random();
          $taskableType = $taskableTypeClass;
        }
      }

      $isCompleted = fake()->boolean(30); // 30% of tasks are completed
      $statusId = $isCompleted ? $completedStatusId : (fake()->optional(0.8, $defaultStatusId)->randomElement($taskStatusIds->all()) ); // Default or random
      if (!$statusId) $statusId = $defaultStatusId; // Fallback if randomElement somehow fails on empty subset

      $dueDate = fake()->dateTimeBetween('-1 month', '+2 months');

      Task::factory()->create([
        'task_status_id' => $statusId,
        'task_priority_id' => fake()->optional(0.8, $defaultPriorityId)->randomElement($taskPriorityIds->all()),
        'assigned_to_user_id' => $activeUsers->isNotEmpty() ? $activeUsers->random() : null,
        'taskable_id' => $taskableId,
        'taskable_type' => $taskableType,
        'due_date' => $dueDate,
        'completed_at' => $isCompleted ? fake()->dateTimeBetween(Carbon::instance($dueDate)->subWeek(), $dueDate) : null,
        'title' => $this->generateTaskTitle($taskableType, $taskableId), // Custom title generator
      ]);
      $progressBar->advance();
    }

    $progressBar->finish();
    $this->command->info("\nTask seeding completed.");
  }

  /**
   * Generate a more relevant task title based on the taskable entity.
   */
  private function generateTaskTitle($taskableType, $taskableId): string
  {
    $baseActions = ['Follow up with', 'Schedule call with', 'Prepare notes for', 'Review documents for', 'Send email to'];
    $suffix = fake()->name(); // Default suffix

    if ($taskableType && $taskableId) {
      $model = app($taskableType)->find($taskableId);
      if ($model) {
        switch ($taskableType) {
          case Contact::class: $suffix = $model->first_name . ' ' . $model->last_name; break;
          case Company::class: $suffix = $model->name; break;
          case Lead::class:
          case Deal::class: $suffix = $model->title; break;
        }
      }
    }
    return fake()->randomElement($baseActions) . ' ' . $suffix;
  }
}
