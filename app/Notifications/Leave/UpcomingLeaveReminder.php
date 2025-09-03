<?php

namespace App\Notifications\Leave;

use App\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HRCore\app\Models\LeaveRequest;

class UpcomingLeaveReminder extends Notification implements ShouldQueue
{
  use Queueable;

  private LeaveRequest $leaveRequest;
  private int $daysUntil;

  /**
   * Create a new notification instance.
   */
  public function __construct(LeaveRequest $leaveRequest, int $daysUntil)
  {
    $this->leaveRequest = $leaveRequest;
    $this->daysUntil = $daysUntil;
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
    $leavePrefs = $hrPrefs['leave'] ?? [];
    
    if ($leavePrefs['reminder_email'] ?? true) {
      $channels[] = 'mail';
    }
    
    if ($leavePrefs['reminder_push'] ?? true) {
      $channels[] = FirebaseChannel::class;
    }
    
    return $channels;
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    $reminderText = match($this->daysUntil) {
      0 => __('today'),
      1 => __('tomorrow'),
      default => __('in :days days', ['days' => $this->daysUntil])
    };

    return (new MailMessage)
      ->subject(__('Leave Reminder - Starting :when', ['when' => ucfirst($reminderText)]))
      ->greeting(__('Hello :name,', ['name' => $notifiable->getFullName()]))
      ->line(__('This is a reminder that your approved leave is starting :when.', [
        'when' => $reminderText
      ]))
      ->line(__('Leave Details:'))
      ->line(__('• Leave Type: :type', ['type' => $this->leaveRequest->leaveType->name]))
      ->line(__('• Duration: :days days', ['days' => $this->leaveRequest->total_days]))
      ->line(__('• Dates: :from_date to :to_date', [
        'from_date' => $this->leaveRequest->from_date->format('M j, Y'),
        'to_date' => $this->leaveRequest->to_date->format('M j, Y')
      ]))
      ->line(__('Please ensure that:'))
      ->line(__('• All pending work is completed or delegated'))
      ->line(__('• Your team is informed about your absence'))
      ->line(__('• Emergency contact information is updated'))
      ->action(__('View Leave Request'), route('hrcore.leaves.show', $this->leaveRequest->id))
      ->line(__('Have a great time off!'));
  }

  /**
   * Get the database representation of the notification.
   */
  public function toDatabase($notifiable): array
  {
    $reminderText = match($this->daysUntil) {
      0 => __('today'),
      1 => __('tomorrow'),
      default => __('in :days days', ['days' => $this->daysUntil])
    };

    return [
      'type' => 'leave_reminder',
      'title' => __('Upcoming Leave Reminder'),
      'message' => __('Your :type leave is starting :when (:days days)', [
        'type' => $this->leaveRequest->leaveType->name,
        'when' => $reminderText,
        'days' => $this->leaveRequest->total_days
      ]),
      'action_url' => route('hrcore.leaves.show', $this->leaveRequest->id),
      'metadata' => [
        'leave_request_id' => $this->leaveRequest->id,
        'leave_type' => $this->leaveRequest->leaveType->name,
        'from_date' => $this->leaveRequest->from_date->toDateString(),
        'to_date' => $this->leaveRequest->to_date->toDateString(),
        'total_days' => $this->leaveRequest->total_days,
        'days_until' => $this->daysUntil,
        'reminder_type' => match($this->daysUntil) {
          0 => 'same_day',
          1 => 'next_day', 
          default => 'advance'
        }
      ],
      'priority' => $this->daysUntil <= 1 ? 'high' : 'normal'
    ];
  }

  /**
   * Get the Firebase representation of the notification.
   */
  public function toFirebase($notifiable)
  {
    $reminderText = match($this->daysUntil) {
      0 => __('today'),
      1 => __('tomorrow'),
      default => __('in :days days', ['days' => $this->daysUntil])
    };

    return [
      'title' => __('Leave Reminder'),
      'body' => __('Your leave starts :when (:days days)', [
        'when' => $reminderText,
        'days' => $this->leaveRequest->total_days
      ]),
      'data' => [
        'type' => 'leave_request',
        'action' => 'reminder',
        'leave_request_id' => $this->leaveRequest->id,
        'days_until' => $this->daysUntil,
        'click_action' => route('hrcore.leaves.show', $this->leaveRequest->id)
      ]
    ];
  }
}