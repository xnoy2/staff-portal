<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * In-app + real-time notification for newsfeed activity.
 * The message adapts per recipient: people who were @mentioned (directly or via
 * @all) see "mentioned you", everyone else sees "posted in the newsfeed".
 * (Email is sent separately as a single BCC announcement — not via this class.)
 */
class NewsfeedActivity extends Notification
{
    use Queueable;

    /**
     * @param array<string> $mentionedIds  user ids who were mentioned (or all, for @all)
     */
    public function __construct(
        public readonly string $postId,
        public readonly string $authorName,
        public readonly string $excerpt,
        public readonly array $mentionedIds,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $mentioned = in_array($notifiable->id, $this->mentionedIds, true);

        return [
            'type'    => $mentioned ? 'newsfeed_mention' : 'newsfeed_post',
            'title'   => $mentioned ? 'You were mentioned' : 'New newsfeed post',
            'message' => $mentioned
                ? "{$this->authorName} mentioned you in a post: \"{$this->excerpt}\""
                : "{$this->authorName} posted in the newsfeed: \"{$this->excerpt}\"",
            'url'     => '/feed',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public static function excerptOf(?string $title, string $body): string
    {
        return Str::limit(trim($title ? $title : strip_tags($body)), 80);
    }
}
