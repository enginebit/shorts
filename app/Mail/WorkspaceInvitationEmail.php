<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Workspace Invitation Email
 *
 * Sent when a user is invited to join a workspace
 * Following dub-main workspace invitation patterns
 */
class WorkspaceInvitationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly WorkspaceInvite $invite,
        public readonly User $invitedBy
    ) {
        // Set queue for email processing
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $workspaceName = $this->invite->workspace->name;
        $inviterName = $this->invitedBy->name;

        return new Envelope(
            subject: "{$inviterName} invited you to join {$workspaceName}",
            from: config('mail.from.address'),
            replyTo: config('mail.from.address'),
            tags: ['workspace-invitation', 'collaboration'],
            metadata: [
                'invite_id' => $this->invite->id,
                'workspace_id' => $this->invite->workspace_id,
                'invited_by' => $this->invitedBy->id,
                'email_type' => 'workspace_invitation',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.workspace-invitation',
            with: [
                'invite' => $this->invite,
                'workspace' => $this->invite->workspace,
                'invitedBy' => $this->invitedBy,
                'acceptUrl' => route('workspace.invites.accept', [
                    'token' => $this->invite->token,
                ]),
                'declineUrl' => route('workspace.invites.decline', [
                    'token' => $this->invite->token,
                ]),
                'expiresAt' => $this->invite->expires_at,
                'role' => $this->invite->role,
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
