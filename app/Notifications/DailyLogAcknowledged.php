<?php

namespace App\Notifications;

use App\Models\DailyLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DailyLogAcknowledged extends Notification
{
    use Queueable;

    public string $date;
    public string $manager;
    public ?string $comment;

    public function __construct(DailyLog $log, User $manager)
    {
        $this->date    = $log->log_date->toDateString();
        $this->manager = $manager->name;
        $this->comment = $log->manager_comment;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'daily_log_ack',
            'title'   => 'Daily log reviewed',
            'message' => "{$this->manager} reviewed your log for {$this->date}."
                . ($this->comment ? " “{$this->comment}”" : ''),
            'url'     => '/my-day?date=' . $this->date,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
