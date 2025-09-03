<?php

namespace App\Notifications\Leave;

use App\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HRCore\app\Models\LeaveRequest;

class NewLeaveRequest extends Notification implements ShouldQueue
{
    use Queueable;

    private LeaveRequest $leaveRequest;

    private string $title;

    private string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
        $this->title = __('New Leave Request');
        $this->message = __('You have a new leave request from :employee for :days days from :from_date to :to_date', [
            'employee' => $leaveRequest->user->getFullName(),
            'days' => $leaveRequest->total_days,
            'from_date' => $leaveRequest->from_date->format('M j, Y'),
            'to_date' => $leaveRequest->to_date->format('M j, Y'),
        ]);
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

        if ($leavePrefs['email'] ?? true) {
            $channels[] = 'mail';
        }

        if ($leavePrefs['push'] ?? true) {
            $channels[] = FirebaseChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = route('hrcore.leave.show', $this->leaveRequest->id);

        return (new MailMessage)
            ->subject($this->title)
            ->greeting(__('Hello :name,', ['name' => $notifiable->getFullName()]))
            ->line(__('A new leave request requires your attention.'))
            ->line(__('Employee: :employee', ['employee' => $this->leaveRequest->user->getFullName()]))
            ->line(__('Leave Type: :type', ['type' => $this->leaveRequest->leaveType->name]))
            ->line(__('Duration: :days days (:from_date to :to_date)', [
                'days' => $this->leaveRequest->total_days,
                'from_date' => $this->leaveRequest->from_date->format('M j, Y'),
                'to_date' => $this->leaveRequest->to_date->format('M j, Y'),
            ]))
            ->when($this->leaveRequest->user_notes, function ($mail) {
                return $mail->line(__('Employee Notes: :notes', ['notes' => $this->leaveRequest->user_notes]));
            })
            ->action(__('Review Leave Request'), $actionUrl)
            ->line(__('Please review and take appropriate action on this leave request.'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'new_leave_request',
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => route('hrcore.leave.show', $this->leaveRequest->id),
            'metadata' => [
                'leave_request_id' => $this->leaveRequest->id,
                'employee_id' => $this->leaveRequest->user_id,
                'employee_name' => $this->leaveRequest->user->getFullName(),
                'leave_type' => $this->leaveRequest->leaveType->name,
                'from_date' => $this->leaveRequest->from_date->toDateString(),
                'to_date' => $this->leaveRequest->to_date->toDateString(),
                'total_days' => $this->leaveRequest->total_days,
                'status' => $this->leaveRequest->status,
                'created_at' => $this->leaveRequest->created_at->toISOString(),
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
            'title' => $this->title,
            'body' => $this->message,
            'data' => [
                'type' => 'leave_request',
                'action' => 'new_request',
                'leave_request_id' => $this->leaveRequest->id,
                'click_action' => route('hrcore.leave.show', $this->leaveRequest->id),
            ],
        ];
    }
}
