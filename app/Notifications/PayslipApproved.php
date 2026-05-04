<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
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
        return ['database', 'broadcast'];
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
