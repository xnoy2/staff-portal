<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DailyLogReminder extends Notification
{
    use Queueable;

    public function __construct(public readonly string $date) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'daily_log_reminder',
            'title'   => 'End-of-day log',
            'message' => "Don't forget to log your activities and submit your EOD for {$this->date}.",
            'url'     => '/my-day?date=' . $this->date,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
