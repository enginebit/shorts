<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\DeliverWebhookJob;
use App\Models\Project;
use App\Models\Webhook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Webhook Service
 *
 * Handles webhook publishing and delivery following dub-main patterns
 * from /lib/webhook/publish.ts and /lib/webhook/qstash.ts
 */
final class WebhookService
{
    /**
     * Available webhook triggers following dub-main patterns
     */
    public const WEBHOOK_TRIGGERS = [
        'link.created',
        'link.updated',
        'link.deleted',
        'link.clicked',
        'lead.created',
        'sale.created',
        'commission.created',
        'partner.enrolled',
    ];

    /**
     * Send workspace-level webhook
     */
    public function sendWorkspaceWebhook(
        string $trigger,
        Project $project,
        array $data
    ): void {
        if (! $project->webhook_enabled) {
            Log::debug('Webhooks disabled for project', [
                'project_id' => $project->id,
                'trigger' => $trigger,
            ]);

            return;
        }

        $webhooks = $this->getActiveWebhooks($project, $trigger);

        if ($webhooks->isEmpty()) {
            Log::debug('No active webhooks found for trigger', [
                'project_id' => $project->id,
                'trigger' => $trigger,
            ]);

            return;
        }

        $this->sendWebhooks($trigger, $webhooks, $data);
    }

    /**
     * Send link-level webhook
     */
    public function sendLinkWebhook(
        string $trigger,
        string $linkId,
        array $data
    ): void {
        // TODO: Implement link-level webhooks
        // This would check for webhooks configured for specific links
        Log::info('Link webhook triggered', [
            'trigger' => $trigger,
            'link_id' => $linkId,
            'data' => $data,
        ]);
    }

    /**
     * Get active webhooks for a project and trigger
     */
    private function getActiveWebhooks(Project $project, string $trigger): \Illuminate\Database\Eloquent\Collection
    {
        return Webhook::where('project_id', $project->id)
            ->whereNull('disabled_at')
            ->whereJsonContains('triggers', $trigger)
            ->get(['id', 'url', 'secret']);
    }

    /**
     * Send webhooks to multiple endpoints
     */
    private function sendWebhooks(
        string $trigger,
        \Illuminate\Database\Eloquent\Collection $webhooks,
        array $data
    ): void {
        if ($webhooks->isEmpty()) {
            return;
        }

        $payload = $this->prepareWebhookPayload($trigger, $data);
        $eventId = Str::uuid()->toString();

        foreach ($webhooks as $webhook) {
            DeliverWebhookJob::dispatch(
                $webhook->id,
                $eventId,
                $trigger,
                $payload
            );
        }

        Log::info('Webhooks queued for delivery', [
            'trigger' => $trigger,
            'webhook_count' => $webhooks->count(),
            'event_id' => $eventId,
        ]);
    }

    /**
     * Prepare webhook payload following dub-main patterns
     */
    private function prepareWebhookPayload(string $trigger, array $data): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'event' => $trigger,
            'created_at' => now()->toISOString(),
            'data' => $data,
        ];
    }

    /**
     * Create a new webhook
     */
    public function createWebhook(
        Project $project,
        string $name,
        string $url,
        array $triggers,
        ?string $secret = null
    ): Webhook {
        $webhook = Webhook::create([
            'project_id' => $project->id,
            'name' => $name,
            'url' => $url,
            'secret' => $secret ?? $this->generateWebhookSecret(),
            'triggers' => $triggers,
        ]);

        Log::info('Webhook created', [
            'webhook_id' => $webhook->id,
            'project_id' => $project->id,
            'url' => $url,
            'triggers' => $triggers,
        ]);

        return $webhook;
    }

    /**
     * Generate a secure webhook secret
     */
    private function generateWebhookSecret(): string
    {
        return 'whsec_'.Str::random(32);
    }

    /**
     * Validate webhook trigger
     */
    public function isValidTrigger(string $trigger): bool
    {
        return in_array($trigger, self::WEBHOOK_TRIGGERS);
    }

    /**
     * Get sample webhook payload for a trigger
     */
    public function getSamplePayload(string $trigger): array
    {
        return match ($trigger) {
            'link.created' => [
                'id' => 'link_'.Str::random(10),
                'domain' => 'dub.sh',
                'key' => 'example',
                'url' => 'https://example.com',
                'title' => 'Example Link',
                'description' => 'An example link',
                'created_at' => now()->toISOString(),
            ],
            'link.clicked' => [
                'link' => [
                    'id' => 'link_'.Str::random(10),
                    'domain' => 'dub.sh',
                    'key' => 'example',
                    'url' => 'https://example.com',
                ],
                'click' => [
                    'timestamp' => now()->toISOString(),
                    'country' => 'US',
                    'city' => 'San Francisco',
                    'device' => 'Desktop',
                    'browser' => 'Chrome',
                    'os' => 'macOS',
                    'referrer' => 'https://google.com',
                ],
            ],
            'lead.created' => [
                'id' => 'lead_'.Str::random(10),
                'link_id' => 'link_'.Str::random(10),
                'customer_id' => 'customer_'.Str::random(10),
                'created_at' => now()->toISOString(),
            ],
            'sale.created' => [
                'id' => 'sale_'.Str::random(10),
                'link_id' => 'link_'.Str::random(10),
                'customer_id' => 'customer_'.Str::random(10),
                'amount' => 9900, // in cents
                'currency' => 'USD',
                'created_at' => now()->toISOString(),
            ],
            default => [
                'message' => 'Sample payload for '.$trigger,
                'timestamp' => now()->toISOString(),
            ],
        };
    }
}
