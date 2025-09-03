<?php

namespace Modules\CRMCore\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

// For FCM
// use NotificationChannels\Fcm\FcmChannel;
// use NotificationChannels\Fcm\FcmMessage;
// use Kreait\Firebase\Messaging\CloudMessage;
// use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class CustomerCustomNotification extends Notification implements ShouldQueue // Implement ShouldQueue
{
    use Queueable;

    public string $title;

    public string $message;

    public ?string $link; // Optional link for redirection

    public ?string $icon; // Optional icon

    public function __construct(string $title, string $message, ?string $link = null, ?string $icon = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->link = $link;
        $this->icon = $icon;
    }

    public function via($notifiable): array
    {
        $channels = ['database']; // Always store in DB for in-app list

        // Add FCM if user has FCM token and push notifications are enabled
        // if (!empty($notifiable->fcm_tokens) && app_settings('push_notifications_enabled', false)) {
        //    $channels[] = FcmChannel::class;
        // }
        // Add mail if email notifications are desired
        // if ($notifiable->email && app_settings('email_notifications_enabled', false)) {
        //    $channels[] = 'mail';
        // }
        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        // This structure is stored in the `data` column of the `notifications` table
        return [
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link, // URL to open in app
            'icon' => $this->icon, // Optional icon identifier
        ];
    }

    // Example for FCM (Firebase Cloud Messaging) - Requires `laravel-notification-channels/fcm`
    /*
    public function toFcm($notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->setNotification(FirebaseNotification::create()
                ->setTitle($this->title)
                ->setBody($this->message)
                // ->setImage('image_url_if_any') // Optional
            )
            // ->setToken($notifiable->fcm_token) // Assuming fcm_token field on User model
            ->setData(['link' => $this->link ?? '', 'icon' => $this->icon ?? '']); // Custom data payload
    }
    */

    // Example for Mail
    /*
    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
                    ->subject($this->title)
                    ->greeting('Hello ' . ($notifiable->first_name ?? 'User') . ',')
                    ->line($this->message);
        if ($this->link) {
            $mailMessage->action('View Details', url($this->link)); // Ensure link is a full URL
        }
        $mailMessage->line('Thank you for using our application!');
        return $mailMessage;
    }
    */
}
