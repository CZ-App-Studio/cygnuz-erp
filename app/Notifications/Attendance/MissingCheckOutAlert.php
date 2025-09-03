<?php

namespace App\Notifications\Attendance;

use App\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HRCore\app\Models\Attendance;

class MissingCheckOutAlert extends Notification implements ShouldQueue
{
    use Queueable;

    private Attendance $attendance;

    /**
     * Create a new notification instance.
     */
    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
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

        if ($attendancePrefs['missing_checkout_email'] ?? true) {
            $channels[] = 'mail';
        }

        if ($attendancePrefs['missing_checkout_push'] ?? true) {
            $channels[] = FirebaseChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $checkInTime = $this->attendance->check_in_time->format('H:i');
        $date = $this->attendance->date->format('M j, Y');

        return (new MailMessage)
            ->subject(__('Missing Check-out - :date', ['date' => $date]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->getFullName()]))
            ->line(__('You forgot to check out yesterday (:date).', ['date' => $date]))
            ->line(__('Check-in Time: :time', ['time' => $checkInTime]))
            ->line(__('Check-out Time: Not recorded'))
            ->line(__('This may affect your attendance record and working hours calculation.'))
            ->line(__('Please submit an attendance regularization request to correct this.'))
            ->action(__('Submit Regularization'), route('hrcore.attendance.regularization'))
            ->line(__('Remember to check out at the end of your working day.'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'missing_checkout_alert',
            'title' => __('Missing Check-out'),
            'message' => __('You forgot to check out on :date', [
                'date' => $this->attendance->date->format('M j, Y'),
            ]),
            'action_url' => route('hrcore.attendance.regularization'),
            'metadata' => [
                'attendance_id' => $this->attendance->id,
                'date' => $this->attendance->date->toDateString(),
                'check_in_time' => $this->attendance->check_in_time->format('H:i:s'),
                'check_out_time' => null,
                'shift_id' => $this->attendance->shift_id,
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
            'title' => __('Missing Check-out'),
            'body' => __('Forgot to check out on :date', [
                'date' => $this->attendance->date->format('M j'),
            ]),
            'data' => [
                'type' => 'attendance',
                'action' => 'missing_checkout',
                'attendance_id' => $this->attendance->id,
                'date' => $this->attendance->date->toDateString(),
                'click_action' => route('hrcore.attendance.regularization'),
            ],
        ];
    }
}
