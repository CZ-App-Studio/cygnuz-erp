<?php

namespace App\Notifications\Attendance;

use App\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HRCore\app\Models\AttendanceRegularization;

class AttendanceRegularizationRequest extends Notification implements ShouldQueue
{
    use Queueable;

    private AttendanceRegularization $regularization;

    /**
     * Create a new notification instance.
     */
    public function __construct(AttendanceRegularization $regularization)
    {
        $this->regularization = $regularization;
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
        $attendancePrefs = $hrPrefs['attendance'] ?? [];

        if ($attendancePrefs['regularization_email'] ?? true) {
            $channels[] = 'mail';
        }

        if ($attendancePrefs['regularization_push'] ?? true) {
            $channels[] = FirebaseChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $typeText = match ($this->regularization->type) {
            'missing_checkin' => __('Missing Check-in'),
            'missing_checkout' => __('Missing Check-out'),
            'wrong_time' => __('Wrong Time'),
            'forgot_punch' => __('Forgot Punch'),
            'other' => __('Other'),
            default => ucfirst($this->regularization->type)
        };

        return (new MailMessage)
            ->subject(__('New Attendance Regularization Request'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->getFullName()]))
            ->line(__('You have received a new attendance regularization request.'))
            ->line(__('Employee: :employee', ['employee' => $this->regularization->user->getFullName()]))
            ->line(__('Date: :date', ['date' => $this->regularization->date->format('M j, Y')]))
            ->line(__('Type: :type', ['type' => $typeText]))
            ->when($this->regularization->requested_check_in_time, function ($mail) {
                return $mail->line(__('Requested Check-in: :time', [
                    'time' => $this->regularization->requested_check_in_time,
                ]));
            })
            ->when($this->regularization->requested_check_out_time, function ($mail) {
                return $mail->line(__('Requested Check-out: :time', [
                    'time' => $this->regularization->requested_check_out_time,
                ]));
            })
            ->line(__('Reason: :reason', ['reason' => $this->regularization->reason]))
            ->action(__('Review Request'), route('hrcore.attendance.regularization.show', $this->regularization->id))
            ->line(__('Please review and approve or reject this request.'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'attendance_regularization_request',
            'title' => __('New Regularization Request'),
            'message' => __(':employee submitted attendance regularization for :date', [
                'employee' => $this->regularization->user->getFullName(),
                'date' => $this->regularization->date->format('M j, Y'),
            ]),
            'action_url' => route('hrcore.attendance.regularization.show', $this->regularization->id),
            'metadata' => [
                'regularization_id' => $this->regularization->id,
                'employee_id' => $this->regularization->user_id,
                'employee_name' => $this->regularization->user->getFullName(),
                'date' => $this->regularization->date->toDateString(),
                'type' => $this->regularization->type,
                'requested_check_in_time' => $this->regularization->requested_check_in_time,
                'requested_check_out_time' => $this->regularization->requested_check_out_time,
                'reason' => $this->regularization->reason,
                'status' => $this->regularization->status,
            ],
            'priority' => 'normal',
        ];
    }

    /**
     * Get the Firebase representation of the notification.
     */
    public function toFirebase($notifiable)
    {
        return [
            'title' => __('Regularization Request'),
            'body' => __(':employee - :date', [
                'employee' => $this->regularization->user->getFullName(),
                'date' => $this->regularization->date->format('M j'),
            ]),
            'data' => [
                'type' => 'attendance',
                'action' => 'regularization_request',
                'regularization_id' => $this->regularization->id,
                'employee_id' => $this->regularization->user_id,
                'click_action' => route('hrcore.attendance.regularization.show', $this->regularization->id),
            ],
        ];
    }
}
