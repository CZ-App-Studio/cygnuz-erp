<?php

namespace App\Notifications\Employee;

use App\Channels\FirebaseChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HRCore\app\Models\Department;

class DepartmentChanged extends Notification implements ShouldQueue
{
  use Queueable;

  private User $employee;
  private ?Department $oldDepartment;
  private Department $newDepartment;
  private string $reason;

  /**
   * Create a new notification instance.
   */
  public function __construct(
    User $employee,
    Department $newDepartment,
    ?Department $oldDepartment = null,
    string $reason = null
  ) {
    $this->employee = $employee;
    $this->newDepartment = $newDepartment;
    $this->oldDepartment = $oldDepartment;
    $this->reason = $reason ?? __('Organizational restructuring');
  }

  /**
   * Get the notification's delivery channels.
   */
  public function via(object $notifiable): array
  {
    $channels = ['database'];
    
    // Check user notification preferences
    $preferences = $notifiable->notification_preferences->preferences ?? [];
    $hrPrefs = $preferences['hrcore'] ?? [];
    $employeePrefs = $hrPrefs['employee'] ?? [];
    
    if ($employeePrefs['profile_changes_email'] ?? true) {
      $channels[] = 'mail';
    }
    
    if ($employeePrefs['profile_changes_push'] ?? true) {
      $channels[] = FirebaseChannel::class;
    }
    
    return $channels;
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    return (new MailMessage)
      ->subject(__('Department Change Notification'))
      ->greeting(__('Hello :name,', ['name' => $notifiable->getFullName()]))
      ->line(__('We want to inform you about an important change to your employment details.'))
      ->line(__('Your department assignment has been updated:'))
      ->when($this->oldDepartment, function ($mail) {
        return $mail->line(__('Previous Department: :department', [
          'department' => $this->oldDepartment->name
        ]));
      })
      ->line(__('New Department: :department', ['department' => $this->newDepartment->name]))
      ->line(__('Effective Date: :date', ['date' => now()->format('M j, Y')]))
      ->line(__('Reason: :reason', ['reason' => $this->reason]))
      ->line(__('This change may affect:'))
      ->line(__('• Your reporting structure'))
      ->line(__('• Team assignments'))
      ->line(__('• Access permissions'))
      ->line(__('• Department-specific policies'))
      ->action(__('View Profile'), route('hrcore.employee.profile'))
      ->line(__('If you have any questions about this change, please contact HR.'));
  }

  /**
   * Get the database representation of the notification.
   */
  public function toDatabase($notifiable): array
  {
    return [
      'type' => 'department_changed',
      'title' => __('Department Changed'),
      'message' => __('You have been moved to :department', [
        'department' => $this->newDepartment->name
      ]),
      'action_url' => route('hrcore.employee.profile'),
      'metadata' => [
        'employee_id' => $this->employee->id,
        'employee_name' => $this->employee->getFullName(),
        'old_department_id' => $this->oldDepartment?->id,
        'old_department_name' => $this->oldDepartment?->name,
        'new_department_id' => $this->newDepartment->id,
        'new_department_name' => $this->newDepartment->name,
        'change_date' => now()->toDateString(),
        'reason' => $this->reason
      ],
      'priority' => 'high'
    ];
  }

  /**
   * Get the Firebase representation of the notification.
   */
  public function toFirebase($notifiable)
  {
    return [
      'title' => __('Department Changed'),
      'body' => __('Moved to :department', [
        'department' => $this->newDepartment->name
      ]),
      'data' => [
        'type' => 'employee',
        'action' => 'department_changed',
        'employee_id' => $this->employee->id,
        'new_department_id' => $this->newDepartment->id,
        'click_action' => route('hrcore.employee.profile')
      ]
    ];
  }
}