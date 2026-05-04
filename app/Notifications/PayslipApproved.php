<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayslipApproved extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $periodFrom,
        public readonly string $periodTo,
        public readonly float  $grossPay,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $gross = '£' . number_format($this->grossPay, 2);

        return (new MailMessage)
            ->subject('Payslip Approved — BCF Staff Portal')
            ->greeting("Hi {$notifiable->name},")
            ->line("Your payslip for **{$this->periodFrom} – {$this->periodTo}** has been approved.")
            ->line("**Gross Pay: {$gross}**")
            ->action('View Payslip', url('/my-payslip'))
            ->salutation('BCF Staff Portal');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'payslip_approved',
            'title'   => 'Payslip Approved',
            'message' => "Your payslip for {$this->periodFrom} – {$this->periodTo} has been approved. Gross pay: £" . number_format($this->grossPay, 2) . '.',
            'url'     => '/my-payslip',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
