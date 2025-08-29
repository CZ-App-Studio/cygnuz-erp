<?php

namespace Modules\CRMCore\database\factories;


use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\CRMCore\app\Models\Task;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Task>
 */
class TaskFactory extends Factory
{

  protected $model = Task::class;
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $taskTitles = [
      'Follow up with',
      'Schedule a meeting with',
      'Prepare presentation for',
      'Send quote to',
      'Discuss requirements with',
      'Finalize contract for',
      'Check in on progress for',
    ];

    $dueDate = fake()->dateTimeBetween('+1 day', '+2 months');
    $shouldHaveReminder = fake()->boolean(30); // 30% chance of having a reminder

    return [
      'title' => fake()->randomElement($taskTitles) . ' ' . fake()->name(), // Generic title
      'description' => fake()->optional(0.7)->paragraph(2), // 70% chance of having a description
      'due_date' => $dueDate,
      'completed_at' => null, // By default, tasks are not completed
      'reminder_at' => $shouldHaveReminder ? Carbon::instance($dueDate)->subDays(rand(1, 3)) : null,

      // foreign keys (task_status_id, task_priority_id, assigned_to_user_id, taskable)
      // will be set more intelligently in the Seeder.

      // created_by_id, updated_by_id, tenant_id are handled by traits
    ];
  }
}
