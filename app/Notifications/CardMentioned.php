<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CardMentioned extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $mentionedByName,
        public readonly string $cardTitle,
        public readonly string $boardId,
        public readonly string $cardId,
        public readonly string $commentBody,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->mentionedByName} mentioned you — BCF Staff Portal")
            ->greeting("Hi {$notifiable->name},")
            ->line("**{$this->mentionedByName}** mentioned you in a comment on the card **{$this->cardTitle}**:")
            ->line('"' . Str::limit($this->commentBody, 280) . '"')
            ->action('View Card', $this->cardUrl())
            ->salutation('BCF Staff Portal');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'card_mentioned',
            'title'   => 'You were mentioned',
            'message' => "{$this->mentionedByName} mentioned you on \"{$this->cardTitle}\".",
            'url'     => "/boards/{$this->boardId}?card={$this->cardId}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    private function cardUrl(): string
    {
        return url(route('boards.show', $this->boardId)) . '?card=' . $this->cardId;
    }
}
