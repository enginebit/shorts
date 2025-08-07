<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\LinkCreatedNotificationEmail;
use App\Mail\WelcomeEmail;
use App\Mail\WorkspaceInvitationEmail;
use App\Models\Link;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email Service
 *
 * Centralized email service for sending various types of emails
 * Integrates with Resend for reliable email delivery
 * Following dub-main email patterns and best practices
 */
final class EmailService
{
    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(User $user, ?Workspace $defaultWorkspace = null): bool
    {
        try {
            Log::info('Sending welcome email', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'workspace_id' => $defaultWorkspace?->id,
            ]);

            Mail::to($user->email)
                ->send(new WelcomeEmail($user, $defaultWorkspace));

            Log::info('Welcome email sent successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send workspace invitation email
     */
    public function sendWorkspaceInvitation(WorkspaceInvite $invite, User $invitedBy): bool
    {
        try {
            Log::info('Sending workspace invitation email', [
                'invite_id' => $invite->id,
                'email' => $invite->email,
                'workspace_id' => $invite->workspace_id,
                'invited_by' => $invitedBy->id,
            ]);

            Mail::to($invite->email)
                ->send(new WorkspaceInvitationEmail($invite, $invitedBy));

            Log::info('Workspace invitation email sent successfully', [
                'invite_id' => $invite->id,
                'email' => $invite->email,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send workspace invitation email', [
                'invite_id' => $invite->id,
                'email' => $invite->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send link created notification email
     */
    public function sendLinkCreatedNotification(Link $link, User $user): bool
    {
        try {
            // Check if user wants link creation notifications
            if (! $this->shouldSendLinkNotification($user)) {
                Log::debug('Skipping link notification - user preference disabled', [
                    'user_id' => $user->id,
                    'link_id' => $link->id,
                ]);

                return true;
            }

            Log::info('Sending link created notification email', [
                'link_id' => $link->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'short_url' => "https://{$link->domain}/{$link->key}",
            ]);

            Mail::to($user->email)
                ->send(new LinkCreatedNotificationEmail($link, $user));

            Log::info('Link created notification email sent successfully', [
                'link_id' => $link->id,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send link created notification email', [
                'link_id' => $link->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send bulk welcome emails (for batch user imports)
     */
    public function sendBulkWelcomeEmails(array $users, ?Workspace $defaultWorkspace = null): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($users as $user) {
            if ($this->sendWelcomeEmail($user, $defaultWorkspace)) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => 'Failed to send welcome email',
                ];
            }

            // Add small delay to avoid rate limiting
            usleep(100000); // 100ms delay
        }

        Log::info('Bulk welcome emails completed', $results);

        return $results;
    }

    /**
     * Send bulk workspace invitations
     */
    public function sendBulkWorkspaceInvitations(array $invites, User $invitedBy): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($invites as $invite) {
            if ($this->sendWorkspaceInvitation($invite, $invitedBy)) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'invite_id' => $invite->id,
                    'email' => $invite->email,
                    'error' => 'Failed to send workspace invitation',
                ];
            }

            // Add small delay to avoid rate limiting
            usleep(100000); // 100ms delay
        }

        Log::info('Bulk workspace invitations completed', $results);

        return $results;
    }

    /**
     * Check if user wants to receive link creation notifications
     */
    private function shouldSendLinkNotification(User $user): bool
    {
        // Check user preferences (this would be stored in user settings)
        // For now, we'll default to false to avoid spam
        // In the future, this could be a user preference setting

        return false; // Disabled by default - users can opt-in
    }

    /**
     * Get email service status and statistics
     */
    public function getEmailStats(): array
    {
        try {
            // This would typically come from your email service provider's API
            // For now, we'll return basic configuration info

            return [
                'service' => 'Resend',
                'configured' => ! empty(config('services.resend.key')),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'mailer' => config('mail.default'),
                'queue_enabled' => config('queue.default') !== 'sync',
            ];

        } catch (Exception $e) {
            Log::error('Failed to get email stats', [
                'error' => $e->getMessage(),
            ]);

            return [
                'service' => 'Unknown',
                'configured' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test email configuration by sending a test email
     */
    public function sendTestEmail(string $toEmail): bool
    {
        try {
            Log::info('Sending test email', ['to' => $toEmail]);

            Mail::raw(
                'This is a test email from '.config('app.name').".\n\n".
                "If you received this email, your email configuration is working correctly!\n\n".
                'Sent at: '.now()->format('Y-m-d H:i:s T')."\n".
                "Service: Resend\n".
                'From: '.config('mail.from.address'),
                function ($message) use ($toEmail) {
                    $message->to($toEmail)
                        ->subject('Test Email from '.config('app.name'))
                        ->from(config('mail.from.address'), config('mail.from.name'));
                }
            );

            Log::info('Test email sent successfully', ['to' => $toEmail]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send test email', [
                'to' => $toEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
