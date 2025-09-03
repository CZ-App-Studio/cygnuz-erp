<?php

namespace App\Notifications\Holiday;

use App\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HRCore\app\Models\Holiday;

class HolidayAnnouncement extends Notification implements ShouldQueue
{
    use Queueable;

    private Holiday $holiday;

    private bool $isUpcoming;

    /**
     * Create a new notification instance.
     */
    public function __construct(Holiday $holiday, bool $isUpcoming = true)
    {
        $this->holiday = $holiday;
        $this->isUpcoming = $isUpcoming;
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
        $holidayPrefs = $hrPrefs['holiday'] ?? [];

        if ($holidayPrefs['announcement_email'] ?? true) {
            $channels[] = 'mail';
        }

        if ($holidayPrefs['announcement_push'] ?? true) {
            $channels[] = FirebaseChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysUntil = now()->diffInDays($this->holiday->date, false);

        $mail = (new MailMessage)
            ->subject(__('Holiday Announcement - :holiday', ['holiday' => $this->holiday->name]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->getFullName()]));

        if ($this->isUpcoming && $daysUntil > 0) {
            $mail->line(__('We want to remind you about an upcoming holiday.'));
        } else {
            $mail->line(__('We want to inform you about a holiday.'));
        }

        $mail->line(__('Holiday: :name', ['name' => $this->holiday->name]))
            ->line(__('Date: :date (:day)', [
                'date' => $this->holiday->date->format('M j, Y'),
                'day' => $this->holiday->date->format('l'),
            ]));

        if ($this->holiday->type !== 'public') {
            $mail->line(__('Type: :type', ['type' => ucfirst($this->holiday->type)]));
        }

        if ($this->holiday->description) {
            $mail->line(__('Description: :description', ['description' => $this->holiday->description]));
        }

        if ($this->holiday->is_half_day) {
            $mail->line(__('This is a half-day holiday (:type)', [
                'type' => $this->holiday->half_day_type === 'morning' ?
                  __('Morning off') : __('Afternoon off'),
            ]));
        }

        if ($this->holiday->is_compensatory) {
            $mail->line(__('This is a compensatory holiday. You may need to work on the designated make-up date.'));
        }

        if ($this->isUpcoming && $daysUntil > 0) {
            $mail->line(__('This holiday is in :days days.', ['days' => $daysUntil]));
        }

        return $mail->line(__('Enjoy your holiday!'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $daysUntil = now()->diffInDays($this->holiday->date, false);

        return [
            'type' => 'holiday_announcement',
            'title' => __('Holiday Announcement'),
            'message' => __(':holiday on :date', [
                'holiday' => $this->holiday->name,
                'date' => $this->holiday->date->format('M j, Y'),
            ]),
            'action_url' => route('hrcore.holidays.index'),
            'metadata' => [
                'holiday_id' => $this->holiday->id,
                'holiday_name' => $this->holiday->name,
                'holiday_date' => $this->holiday->date->toDateString(),
                'holiday_type' => $this->holiday->type,
                'is_half_day' => $this->holiday->is_half_day,
                'half_day_type' => $this->holiday->half_day_type,
                'is_compensatory' => $this->holiday->is_compensatory,
                'days_until' => $daysUntil,
                'is_upcoming' => $this->isUpcoming,
                'description' => $this->holiday->description,
            ],
            'priority' => $daysUntil <= 3 ? 'high' : 'normal',
        ];
    }

    /**
     * Get the Firebase representation of the notification.
     */
    public function toFirebase($notifiable)
    {
        $daysUntil = now()->diffInDays($this->holiday->date, false);

        return [
            'title' => __('Holiday: :name', ['name' => $this->holiday->name]),
            'body' => __(':date (:days days)', [
                'date' => $this->holiday->date->format('M j'),
                'days' => $daysUntil > 0 ? "in {$daysUntil}" : 'today',
            ]),
            'data' => [
                'type' => 'holiday',
                'action' => 'announcement',
                'holiday_id' => $this->holiday->id,
                'holiday_date' => $this->holiday->date->toDateString(),
                'days_until' => $daysUntil,
                'click_action' => route('hrcore.holidays.index'),
            ],
        ];
    }
}
