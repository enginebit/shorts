<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Link;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Link Created Notification Email
 *
 * Sent when a new link is created (optional notification)
 * Following dub-main link notification patterns
 */
class LinkCreatedNotificationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Link $link,
        public readonly User $user
    ) {
        // Set queue for email processing
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $shortUrl = "https://{$this->link->domain}/{$this->link->key}";

        return new Envelope(
            subject: "Your short link is ready: {$shortUrl}",
            from: config('mail.from.address'),
            replyTo: config('mail.from.address'),
            tags: ['link-created', 'notification'],
            metadata: [
                'link_id' => $this->link->id,
                'user_id' => $this->user->id,
                'workspace_id' => $this->link->workspace_id,
                'email_type' => 'link_created',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $shortUrl = "https://{$this->link->domain}/{$this->link->key}";

        return new Content(
            view: 'emails.link-created',
            with: [
                'link' => $this->link,
                'user' => $this->user,
                'shortUrl' => $shortUrl,
                'originalUrl' => $this->link->url,
                'analyticsUrl' => route('links.analytics', ['link' => $this->link->id]),
                'editUrl' => route('links.edit', ['link' => $this->link->id]),
                'createdAt' => $this->link->created_at,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
