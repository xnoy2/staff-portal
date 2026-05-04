<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $status,
        public readonly string $leaveType,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $reviewNotes = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = ucfirst($this->status);
        $type        = ucfirst(str_replace('_', ' ', $this->leaveType));
        $isApproved  = $this->status === 'approved';

        $mail = (new MailMessage)
            ->subject("Leave {$statusLabel} — BCF Staff Portal")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your {$type} leave request has been **{$this->status}**.")
            ->line("**Period:** {$this->startDate} – {$this->endDate}");

        if ($this->reviewNotes) {
            $mail->line("**Note from reviewer:** {$this->reviewNotes}");
        }

        return $mail
            ->action('View Leave', url('/leave'))
            ->salutation('BCF Staff Portal');
    }

    public function toArray(object $notifiable): array
    {
        $statusLabel = ucfirst($this->status);
        $type        = ucfirst(str_replace('_', ' ', $this->leaveType));

        return [
            'type'    => 'leave_status',
            'title'   => "Leave {$statusLabel}",
            'message' => "{$type} leave ({$this->startDate} – {$this->endDate}) has been {$this->status}.",
            'notes'   => $this->reviewNotes,
            'url'     => '/leave',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
