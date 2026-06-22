<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class CardDueReminder extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $cardTitle,
        public readonly string $boardId,
        public readonly string $cardId,
        public readonly string $dueAtIso,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Reminder: \"{$this->cardTitle}\" is due — BCF Staff Portal")
            ->greeting("Hi {$notifiable->name},")
            ->line('A card you are on is coming due:')
            ->line("**{$this->cardTitle}** is due {$this->dueLong()}.")
            ->action('View Card', $this->cardUrl())
            ->salutation('BCF Staff Portal');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'card_due',
            'title'   => 'Card due soon',
            'message' => "\"{$this->cardTitle}\" is due {$this->dueShort()}.",
            'url'     => "/boards/{$this->boardId}?card={$this->cardId}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    private function dueLong(): string
    {
        return Carbon::parse($this->dueAtIso)->format('D, j M Y, g:i A');
    }

    private function dueShort(): string
    {
        return Carbon::parse($this->dueAtIso)->format('j M, g:i A');
    }

    private function cardUrl(): string
    {
        return url(route('boards.show', $this->boardId)) . '?card=' . $this->cardId;
    }
}
