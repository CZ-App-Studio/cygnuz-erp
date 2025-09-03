<?php

namespace App\Notifications\Leave;

use App\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HRCore\app\Models\UserAvailableLeave;

class LeaveBalanceUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    private UserAvailableLeave $leaveBalance;

    private string $changeType; // 'credit', 'debit', 'reset', 'adjustment'

    private float $previousBalance;

    private string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        UserAvailableLeave $leaveBalance,
        string $changeType,
        float $previousBalance,
        ?string $reason = null
    ) {
        $this->leaveBalance = $leaveBalance;
        $this->changeType = $changeType;
        $this->previousBalance = $previousBalance;
        $this->reason = $reason ?? __('Administrative adjustment');
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

        if ($leavePrefs['balance_updates_email'] ?? false) {
            $channels[] = 'mail';
        }

        if ($leavePrefs['balance_updates_push'] ?? false) {
            $channels[] = FirebaseChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $changeAmount = abs($this->leaveBalance->available_leaves - $this->previousBalance);
        $actionText = match ($this->changeType) {
            'credit' => __('credited to'),
            'debit' => __('debited from'),
            'reset' => __('reset for'),
            'adjustment' => __('adjusted for'),
            default => __('updated for')
        };

        return (new MailMessage)
            ->subject(__('Leave Balance Updated'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->getFullName()]))
            ->line(__('Your :leave_type leave balance has been updated.', [
                'leave_type' => $this->leaveBalance->leaveType->name,
            ]))
            ->line(__('Previous Balance: :previous days', ['previous' => $this->previousBalance]))
            ->line(__('Current Balance: :current days', ['current' => $this->leaveBalance->available_leaves]))
            ->line(__('Change: :amount days :action your account', [
                'amount' => $changeAmount,
                'action' => $actionText,
            ]))
            ->line(__('Reason: :reason', ['reason' => $this->reason]))
            ->action(__('View Leave Balance'), route('hrcore.leave.balance'))
            ->line(__('If you have any questions about this change, please contact HR.'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $changeAmount = $this->leaveBalance->available_leaves - $this->previousBalance;

        return [
            'type' => 'leave_balance_updated',
            'title' => __('Leave Balance Updated'),
            'message' => __('Your :leave_type balance was updated by :amount days', [
                'leave_type' => $this->leaveBalance->leaveType->name,
                'amount' => $changeAmount > 0 ? '+'.$changeAmount : $changeAmount,
            ]),
            'action_url' => route('hrcore.leave.balance'),
            'metadata' => [
                'leave_type_id' => $this->leaveBalance->leave_type_id,
                'leave_type' => $this->leaveBalance->leaveType->name,
                'previous_balance' => $this->previousBalance,
                'current_balance' => $this->leaveBalance->available_leaves,
                'change_amount' => $changeAmount,
                'change_type' => $this->changeType,
                'reason' => $this->reason,
                'year' => $this->leaveBalance->year,
            ],
            'priority' => 'normal',
        ];
    }

    /**
     * Get the Firebase representation of the notification.
     */
    public function toFirebase($notifiable)
    {
        $changeAmount = $this->leaveBalance->available_leaves - $this->previousBalance;

        return [
            'title' => __('Leave Balance Updated'),
            'body' => __('Your :leave_type balance: :amount days', [
                'leave_type' => $this->leaveBalance->leaveType->name,
                'amount' => $changeAmount > 0 ? '+'.$changeAmount : $changeAmount,
            ]),
            'data' => [
                'type' => 'leave_balance',
                'action' => 'balance_updated',
                'leave_type_id' => $this->leaveBalance->leave_type_id,
                'click_action' => route('hrcore.leave.balance'),
            ],
        ];
    }
}
