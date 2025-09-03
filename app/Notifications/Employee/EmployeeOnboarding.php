<?php

namespace App\Notifications\Employee;

use App\Channels\FirebaseChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeOnboarding extends Notification implements ShouldQueue
{
    use Queueable;

    private User $employee;

    private array $onboardingTasks;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $employee, array $onboardingTasks = [])
    {
        $this->employee = $employee;
        $this->onboardingTasks = $onboardingTasks ?: [
            'Complete profile setup',
            'Upload profile photo',
            'Review company policies',
            'Complete mandatory training',
            'Set up bank account details',
        ];
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'mail'];

        // Check user notification preferences
        $preferences = $notifiable->notification_preferences->preferences ?? [];
        $hrPrefs = $preferences['hrcore'] ?? [];
        $employeePrefs = $hrPrefs['employee'] ?? [];

        if ($employeePrefs['onboarding_push'] ?? true) {
            $channels[] = FirebaseChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('Welcome to :company - Complete Your Onboarding', [
                'company' => config('app.name'),
            ]))
            ->greeting(__('Welcome :name!', ['name' => $notifiable->getFullName()]))
            ->line(__('We\'re excited to have you join our team at :company.', [
                'company' => config('app.name'),
            ]))
            ->line(__('To complete your onboarding process, please complete the following tasks:'));

        foreach ($this->onboardingTasks as $task) {
            $mail->line(__('• :task', ['task' => $task]));
        }

        return $mail
            ->action(__('Complete Onboarding'), route('hrcore.employee.profile'))
            ->line(__('If you have any questions, please don\'t hesitate to contact HR or your manager.'))
            ->line(__('Welcome aboard!'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'employee_onboarding',
            'title' => __('Welcome - Complete Onboarding'),
            'message' => __('Welcome to :company! Complete your onboarding checklist', [
                'company' => config('app.name'),
            ]),
            'action_url' => route('hrcore.employee.profile'),
            'metadata' => [
                'employee_id' => $this->employee->id,
                'employee_name' => $this->employee->getFullName(),
                'onboarding_tasks' => $this->onboardingTasks,
                'total_tasks' => count($this->onboardingTasks),
                'start_date' => $this->employee->start_date?->toDateString(),
                'department' => $this->employee->department?->name,
            ],
            'priority' => 'high',
        ];
    }

    /**
     * Get the Firebase representation of the notification.
     */
    public function toFirebase($notifiable)
    {
        return [
            'title' => __('Welcome to :company', ['company' => config('app.name')]),
            'body' => __('Complete your onboarding checklist'),
            'data' => [
                'type' => 'employee',
                'action' => 'onboarding',
                'employee_id' => $this->employee->id,
                'tasks_count' => count($this->onboardingTasks),
                'click_action' => route('hrcore.employee.profile'),
            ],
        ];
    }
}
