<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class JobAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $jobTitle,
        public readonly string $jobDate,
        public readonly string $jobId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'job_assigned',
            'title'   => 'Assigned to Job',
            'message' => "You have been assigned to \"{$this->jobTitle}\" on {$this->jobDate}.",
            'url'     => '/jobs',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
