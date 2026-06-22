<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsfeedPostMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $authorName,
        public ?string $postTitle,
        public string $excerpt,
        public string $type,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        $what = match ($this->type) {
            'blog'        => 'a blog post',
            'event'       => 'an event',
            'recognition' => 'a recognition',
            default       => 'a post',
        };

        return new Envelope(
            subject: "{$this->authorName} shared {$what} on the Newsfeed — Staff Portal",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.newsfeed-post');
    }
}
