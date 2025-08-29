<?php

namespace Modules\CRMCore\app\Services;

use App\Models\User;
use App\Services\Settings\ModuleSettingsService;
use Illuminate\Support\Facades\Notification;
use Modules\CRMCore\app\Models\Deal;
use Modules\CRMCore\app\Models\Lead;
use Modules\CRMCore\app\Models\Task;
use Modules\CRMCore\app\Notifications\DealStageChangedNotification;
use Modules\CRMCore\app\Notifications\LeadAssignedNotification;
use Modules\CRMCore\app\Notifications\TaskDueNotification;

class CRMNotificationService
{
    protected ModuleSettingsService $settingsService;

    public function __construct(ModuleSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Send notification when a lead is assigned
     */
    public function notifyLeadAssignment(Lead $lead, User $assignedUser): void
    {
        if (! $this->settingsService->get('CRMCore', 'notify_lead_assignment', true)) {
            return;
        }

        // Check if notification class exists, create a simple notification if not
        if (class_exists(LeadAssignedNotification::class)) {
            $assignedUser->notify(new LeadAssignedNotification($lead));
        } else {
            // Fallback to database notification
            $assignedUser->notifyNow(
                new class($lead) extends \Illuminate\Notifications\Notification
                {
                    private $lead;

                    public function __construct($lead)
                    {
                        $this->lead = $lead;
                    }

                    public function via($notifiable)
                    {
                        return ['database'];
                    }

                    public function toArray($notifiable)
                    {
                        return [
                            'message' => "You have been assigned a new lead: {$this->lead->full_name}",
                            'lead_id' => $this->lead->id,
                            'lead_name' => $this->lead->full_name,
                            'type' => 'lead_assignment',
                        ];
                    }
                }
            );
        }
    }

    /**
     * Send notification when a deal stage changes
     */
    public function notifyDealStageChange(Deal $deal, string $oldStage, string $newStage): void
    {
        if (! $this->settingsService->get('CRMCore', 'notify_deal_stage_change', true)) {
            return;
        }

        if (! $deal->assignedToUser) {
            return;
        }

        // Check if notification class exists
        if (class_exists(DealStageChangedNotification::class)) {
            $deal->assignedToUser->notify(new DealStageChangedNotification($deal, $oldStage, $newStage));
        } else {
            // Fallback to database notification
            $deal->assignedToUser->notifyNow(
                new class($deal, $oldStage, $newStage) extends \Illuminate\Notifications\Notification
                {
                    private $deal;

                    private $oldStage;

                    private $newStage;

                    public function __construct($deal, $oldStage, $newStage)
                    {
                        $this->deal = $deal;
                        $this->oldStage = $oldStage;
                        $this->newStage = $newStage;
                    }

                    public function via($notifiable)
                    {
                        return ['database'];
                    }

                    public function toArray($notifiable)
                    {
                        return [
                            'message' => "Deal '{$this->deal->title}' stage changed from {$this->oldStage} to {$this->newStage}",
                            'deal_id' => $this->deal->id,
                            'deal_title' => $this->deal->title,
                            'old_stage' => $this->oldStage,
                            'new_stage' => $this->newStage,
                            'type' => 'deal_stage_change',
                        ];
                    }
                }
            );
        }
    }

    /**
     * Send notification when a task is due
     */
    public function notifyTaskDue(Task $task): void
    {
        if (! $this->settingsService->get('CRMCore', 'notify_task_due', true)) {
            return;
        }

        if (! $task->assignedToUser) {
            return;
        }

        // Check if notification class exists
        if (class_exists(TaskDueNotification::class)) {
            $task->assignedToUser->notify(new TaskDueNotification($task));
        } else {
            // Fallback to database notification
            $task->assignedToUser->notifyNow(
                new class($task) extends \Illuminate\Notifications\Notification
                {
                    private $task;

                    public function __construct($task)
                    {
                        $this->task = $task;
                    }

                    public function via($notifiable)
                    {
                        return ['database'];
                    }

                    public function toArray($notifiable)
                    {
                        return [
                            'message' => "Task '{$this->task->title}' is due soon",
                            'task_id' => $this->task->id,
                            'task_title' => $this->task->title,
                            'due_date' => $this->task->due_date,
                            'type' => 'task_due',
                        ];
                    }
                }
            );
        }
    }

    /**
     * Check if task reminders are enabled
     */
    public function isTaskReminderEnabled(): bool
    {
        return $this->settingsService->get('CRMCore', 'task_reminder_enabled', true);
    }

    /**
     * Get task reminder hours before due date
     */
    public function getTaskReminderHours(): int
    {
        return (int) $this->settingsService->get('CRMCore', 'task_reminder_before_hours', 24);
    }

    /**
     * Send task reminders for upcoming tasks
     */
    public function sendTaskReminders(): void
    {
        if (! $this->isTaskReminderEnabled()) {
            return;
        }

        $reminderHours = $this->getTaskReminderHours();
        $reminderTime = now()->addHours($reminderHours);

        // Find tasks due within the reminder window
        $tasks = Task::where('due_date', '<=', $reminderTime)
            ->where('due_date', '>', now())
            ->whereNotNull('assigned_to_user_id')
            ->whereDoesntHave('reminders', function ($query) {
                $query->where('sent_at', '>=', now()->subDay());
            })
            ->with('assignedToUser')
            ->get();

        foreach ($tasks as $task) {
            $this->notifyTaskDue($task);

            // Record that reminder was sent (if reminders table exists)
            if (\Schema::hasTable('task_reminders')) {
                \DB::table('task_reminders')->insert([
                    'task_id' => $task->id,
                    'user_id' => $task->assigned_to_user_id,
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Check if lead auto-conversion is enabled
     */
    public function isLeadAutoConvertEnabled(): bool
    {
        $days = $this->settingsService->get('CRMCore', 'lead_auto_convert_days', 30);

        return $days > 0;
    }

    /**
     * Get lead auto-conversion days
     */
    public function getLeadAutoConvertDays(): int
    {
        return (int) $this->settingsService->get('CRMCore', 'lead_auto_convert_days', 30);
    }

    /**
     * Check if deal auto-close is enabled
     */
    public function isDealAutoCloseEnabled(): bool
    {
        $days = $this->settingsService->get('CRMCore', 'deal_auto_close_days', 90);

        return $days > 0;
    }

    /**
     * Get deal auto-close days
     */
    public function getDealAutoCloseDays(): int
    {
        return (int) $this->settingsService->get('CRMCore', 'deal_auto_close_days', 90);
    }
}
