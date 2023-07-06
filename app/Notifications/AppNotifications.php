<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class AppNotifications extends Notification
{
    use Queueable;
    protected $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'firebase'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->data['message'],
            'x' => [
                'screen' => $this->data['screen'],
                'res_id' => $this->data['res_id'] ?? null,
            ],
        ];
    }

    public function toFirebase($notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle($this->data['title'])
            ->withBody($this->data['message'])
            ->withAdditionalData([
                'screen' => $this->data['screen'],
                'res_id' => $this->data['res_id'] ?? null,
            ])
            ->withPriority('high')->asMessage($this->data['fcmTokens']);
    }
}
