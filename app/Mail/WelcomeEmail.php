<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Welcome Email for new users
 *
 * Sent when a user successfully registers and verifies their account
 * Following dub-main email patterns for user onboarding
 */
class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?Workspace $defaultWorkspace = null
    ) {
        // Set queue for email processing
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('app.name').' - Your URL shortener is ready!',
            from: config('mail.from.address'),
            replyTo: config('mail.from.address'),
            tags: ['welcome', 'onboarding'],
            metadata: [
                'user_id' => $this->user->id,
                'workspace_id' => $this->defaultWorkspace?->id,
                'email_type' => 'welcome',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->user,
                'workspace' => $this->defaultWorkspace,
                'loginUrl' => route('login'),
                'dashboardUrl' => $this->defaultWorkspace
                    ? route('dashboard', ['workspace' => $this->defaultWorkspace->slug])
                    : route('dashboard'),
                'supportEmail' => config('mail.from.address'),
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
