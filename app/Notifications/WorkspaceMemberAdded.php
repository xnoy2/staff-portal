<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceMemberAdded extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $workspaceName,
        public readonly string $workspaceId,
        public readonly string $addedByName,
        public readonly string $role = 'member',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->mailer('resend')
            ->subject("You've been added to {$this->workspaceName} — BCF Staff Portal")
            ->greeting("Hi {$notifiable->name},")
            ->line("**{$this->addedByName}** added you to the **{$this->workspaceName}** workspace as a {$this->role}.")
            ->line('You can now see and collaborate on all of its boards.')
            ->action('Open Workspace', url(route('workspaces.show', $this->workspaceId)))
            ->salutation('BCF Staff Portal');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'workspace_member_added',
            'title'   => 'Added to a workspace',
            'message' => "{$this->addedByName} added you to \"{$this->workspaceName}\".",
            'url'     => "/workspaces/{$this->workspaceId}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
