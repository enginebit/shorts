<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Project;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Limit Email Job
 *
 * Sends usage limit notification emails to workspace owners
 * following dub-main patterns from /lib/cron/send-limit-email.ts
 */
final class SendLimitEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function __construct(
        private readonly string $projectId,
        private readonly string $type,
        private readonly array $emails = []
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project = Project::with(['users'])->find($this->projectId);

        if (! $project) {
            Log::error('Project not found for limit email', [
                'project_id' => $this->projectId,
                'type' => $this->type,
            ]);

            return;
        }

        try {
            $this->sendLimitEmails($project);
        } catch (Exception $e) {
            Log::error('Failed to send limit email', [
                'project_id' => $this->projectId,
                'type' => $this->type,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send limit emails to project owners and specified emails
     */
    private function sendLimitEmails(Project $project): void
    {
        $recipients = $this->getRecipients($project);

        if (empty($recipients)) {
            Log::warning('No recipients found for limit email', [
                'project_id' => $this->projectId,
                'type' => $this->type,
            ]);

            return;
        }

        $emailData = $this->prepareEmailData($project);

        foreach ($recipients as $email) {
            try {
                $this->sendEmail($email, $emailData);

                Log::info('Limit email sent successfully', [
                    'project_id' => $this->projectId,
                    'type' => $this->type,
                    'email' => $email,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to send limit email to recipient', [
                    'project_id' => $this->projectId,
                    'type' => $this->type,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Record that this email type was sent
        $this->recordSentEmail($project);
    }

    /**
     * Get email recipients (project owners + specified emails)
     */
    private function getRecipients(Project $project): array
    {
        $recipients = $this->emails;

        // Add project owners
        $ownerEmails = $project->users()
            ->wherePivot('role', 'owner')
            ->pluck('email')
            ->toArray();

        return array_unique(array_merge($recipients, $ownerEmails));
    }

    /**
     * Prepare email data based on limit type
     */
    private function prepareEmailData(Project $project): array
    {
        $isUsageLimit = str_contains($this->type, 'UsageLimit');
        $isLinksLimit = str_contains($this->type, 'LinksLimit');

        if ($isUsageLimit) {
            return [
                'subject' => 'Shorts Alert: Clicks Limit Exceeded',
                'template' => 'emails.clicks-exceeded',
                'data' => [
                    'project' => $project,
                    'type' => $this->type,
                    'usage' => $project->clicks_usage ?? 0,
                    'limit' => $project->clicks_limit ?? 1000,
                ],
            ];
        }

        if ($isLinksLimit) {
            $percentage = $project->links_limit > 0
                ? round(($project->links_usage / $project->links_limit) * 100)
                : 0;

            return [
                'subject' => "Shorts Alert: {$project->name} has used {$percentage}% of its links limit for the month.",
                'template' => 'emails.links-limit-alert',
                'data' => [
                    'project' => $project,
                    'percentage' => $percentage,
                    'usage' => $project->links_usage ?? 0,
                    'limit' => $project->links_limit ?? 10,
                ],
            ];
        }

        return [
            'subject' => 'Shorts Alert: Usage Notification',
            'template' => 'emails.generic-alert',
            'data' => [
                'project' => $project,
                'type' => $this->type,
            ],
        ];
    }

    /**
     * Send individual email
     */
    private function sendEmail(string $email, array $emailData): void
    {
        // For now, we'll log the email content
        // In production, this would use Laravel's Mail facade
        Log::info('Sending limit email', [
            'to' => $email,
            'subject' => $emailData['subject'],
            'template' => $emailData['template'],
            'project_id' => $this->projectId,
        ]);

        // TODO: Implement actual email sending with Mail facade
        // Mail::to($email)->send(new LimitEmail($emailData));
    }

    /**
     * Record that this email type was sent for this project
     */
    private function recordSentEmail(Project $project): void
    {
        // TODO: Create sent_emails table and record
        Log::info('Recorded sent email', [
            'project_id' => $this->projectId,
            'type' => $this->type,
            'sent_at' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Send limit email job failed permanently', [
            'project_id' => $this->projectId,
            'type' => $this->type,
            'error' => $exception->getMessage(),
        ]);
    }
}
