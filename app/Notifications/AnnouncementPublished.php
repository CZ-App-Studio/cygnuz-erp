<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use Modules\Announcement\app\Models\Announcement;

class AnnouncementPublished extends Notification implements ShouldQueue
{
    use Queueable;

    protected $announcement;
    protected $notificationType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Announcement $announcement, $type = 'new')
    {
        $this->announcement = $announcement;
        $this->notificationType = $type; // 'new', 'urgent', 'reminder', 'expiring'
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = ['database'];
        
        // Add mail channel if announcement requires email
        if ($this->announcement->send_email) {
            $channels[] = 'mail';
        }
        
        // Could add other channels like SMS, Slack, etc.
        // if ($this->announcement->send_sms) {
        //     $channels[] = 'sms';
        // }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $subject = match($this->notificationType) {
            'urgent' => '🚨 Urgent: ' . $this->announcement->title,
            'reminder' => '🔔 Reminder: ' . $this->announcement->title,
            'expiring' => '⏰ Expiring Soon: ' . $this->announcement->title,
            default => '📢 ' . $this->announcement->title
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line($this->announcement->description)
            ->when($this->announcement->priority === 'urgent', function ($message) {
                return $message->error('This is an urgent announcement requiring immediate attention.');
            })
            ->when($this->announcement->priority === 'high', function ($message) {
                return $message->line('This is a high priority announcement.');
            })
            ->action('Read Full Announcement', route('announcements.show', $this->announcement->id))
            ->when($this->announcement->requires_acknowledgment, function ($message) {
                return $message->line('⚠️ This announcement requires your acknowledgment.');
            })
            ->when($this->announcement->expiry_date, function ($message) {
                return $message->line('Valid until: ' . $this->announcement->expiry_date->format('M d, Y'));
            })
            ->line('Thank you for your attention.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'announcement',
            'subtype' => $this->notificationType,
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'message' => $this->announcement->description,
            'priority' => $this->announcement->priority,
            'announcement_type' => $this->announcement->type,
            'requires_acknowledgment' => $this->announcement->requires_acknowledgment,
            'url' => route('announcements.show', $this->announcement->id),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'expires_at' => $this->announcement->expiry_date?->toDateTimeString(),
        ];
    }

    /**
     * Get icon based on announcement type and priority
     */
    protected function getIcon()
    {
        if ($this->announcement->priority === 'urgent') {
            return 'bx-error-circle';
        }
        
        return match($this->announcement->type) {
            'important' => 'bx-info-circle',
            'event' => 'bx-calendar-event',
            'policy' => 'bx-file',
            'update' => 'bx-refresh',
            default => 'bx-bell'
        };
    }

    /**
     * Get color based on priority
     */
    protected function getColor()
    {
        return match($this->announcement->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'normal' => 'primary',
            'low' => 'secondary',
            default => 'info'
        };
    }

    /**
     * Determine if the notification should be sent.
     */
    public function shouldSend($notifiable, $channel)
    {
        // Don't send if user has already read the announcement
        if ($this->notificationType === 'new') {
            return !$this->announcement->isReadBy($notifiable);
        }
        
        return true;
    }
}