<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * In-app + real-time notification when a user is @mentioned in a newsfeed
 * comment (directly or via @all). Email is sent separately as a single BCC.
 */
class PostCommentMention extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $commenterName,
        public readonly string $excerpt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'newsfeed_comment_mention',
            'title'   => 'You were mentioned',
            'message' => "{$this->commenterName} mentioned you in a comment: \"{$this->excerpt}\"",
            'url'     => '/feed',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public static function excerptOf(string $body): string
    {
        return Str::limit(trim($body), 80);
    }
}
