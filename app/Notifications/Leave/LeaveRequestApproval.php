<?php

namespace App\Notifications\Leave;

use App\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HRCore\app\Models\LeaveRequest;

class LeaveRequestApproval extends Notification implements ShouldQueue
{
    use Queueable;

    private LeaveRequest $leaveRequest;

    private string $status;

    private string $title;

    private string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(LeaveRequest $request, string $status)
    {
        $this->leaveRequest = $request;
        $this->status = $status;

        $statusText = match ($status) {
            'approved' => __('approved'),
            'rejected' => __('rejected'),
            'cancelled' => __('cancelled'),
            default => $status
        };

        $this->title = __('Leave Request :status', ['status' => ucfirst($statusText)]);
        $this->message = __('Your leave request for :days days from :from_date to :to_date has been :status', [
            'days' => $request->total_days,
            'from_date' => $request->from_date->format('M j, Y'),
            'to_date' => $request->to_date->format('M j, Y'),
            'status' => $statusText,
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
        $actionUrl = route('hrcore.leaves.show', $this->leaveRequest->id);
        $approver = $this->leaveRequest->approved_by_id ?
            \App\Models\User::find($this->leaveRequest->approved_by_id)?->getFullName() :
            (\App\Models\User::find($this->leaveRequest->rejected_by_id)?->getFullName() ?? __('Manager'));

        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting(__('Hello :name,', ['name' => $notifiable->getFullName()]))
            ->line(__('Your leave request has been :status by :approver.', [
                'status' => strtolower($this->status),
                'approver' => $approver,
            ]))
            ->line(__('Leave Details:'))
            ->line(__('• Leave Type: :type', ['type' => $this->leaveRequest->leaveType->name]))
            ->line(__('• Duration: :days days', ['days' => $this->leaveRequest->total_days]))
            ->line(__('• Dates: :from_date to :to_date', [
                'from_date' => $this->leaveRequest->from_date->format('M j, Y'),
                'to_date' => $this->leaveRequest->to_date->format('M j, Y'),
            ]));

        if ($this->leaveRequest->approval_notes) {
            $mail->line(__('Manager Notes: :notes', ['notes' => $this->leaveRequest->approval_notes]));
        }

        if ($this->status === 'approved') {
            $mail->line(__('Your leave has been approved. Please ensure proper handover of responsibilities.'));
        } elseif ($this->status === 'rejected') {
            $mail->line(__('Please contact your manager if you need clarification about the rejection.'));
        }

        return $mail->action(__('View Leave Request'), $actionUrl);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'leave_request_'.$this->status,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => route('hrcore.leaves.show', $this->leaveRequest->id),
            'metadata' => [
                'leave_request_id' => $this->leaveRequest->id,
                'leave_type' => $this->leaveRequest->leaveType->name,
                'from_date' => $this->leaveRequest->from_date->toDateString(),
                'to_date' => $this->leaveRequest->to_date->toDateString(),
                'total_days' => $this->leaveRequest->total_days,
                'status' => $this->status,
                'approval_notes' => $this->leaveRequest->approval_notes,
                'actioned_at' => now()->toISOString(),
                'actioned_by' => $this->leaveRequest->approved_by_id ?? $this->leaveRequest->rejected_by_id,
            ],
            'priority' => $this->status === 'approved' ? 'high' : 'normal',
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
                'action' => $this->status,
                'leave_request_id' => $this->leaveRequest->id,
                'click_action' => route('hrcore.leaves.show', $this->leaveRequest->id),
            ],
        ];
    }
}
