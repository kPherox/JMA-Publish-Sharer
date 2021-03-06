<?php

namespace App\Notifications;

use App\Eloquents\LinkedSocialAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Line\LineChannel;
use NotificationChannels\Line\LineMessage;
use NotificationChannels\Twitter\TwitterChannel;
use NotificationChannels\Twitter\TwitterStatusUpdate;

class TestNotify extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable) : array
    {
        $via = [];

        if (! $notifiable instanceof LinkedSocialAccount) {
            \Log::debug(get_class($notifiable));

            return $via;
        }

        switch ($notifiable->provider_name) {
            case 'twitter':
                $via[] = TwitterChannel::class;
                break;
            case 'line':
                $via[] = LineChannel::class;
                break;
            default:
                break;
        }

        return $via;
    }

    /**
     * Get the twitter status of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toTwitter($notifiable) : TwitterStatusUpdate
    {
        return new TwitterStatusUpdate($this->message);
    }

    /**
     * Get the line message of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toLine($notifiable)
    {
        return new LineMessage($this->message);
    }
}
