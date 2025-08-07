<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Webhook;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Delivery Job
 *
 * Delivers webhook events to external endpoints with retry logic
 * following dub-main webhook patterns from /lib/webhook/qstash.ts
 */
final class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 30;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function __construct(
        private readonly string $webhookId,
        private readonly string $eventId,
        private readonly string $event,
        private readonly array $payload,
        private readonly int $attempt = 1
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);

        if (! $webhook) {
            Log::error('Webhook not found for delivery', [
                'webhook_id' => $this->webhookId,
                'event_id' => $this->eventId,
            ]);

            return;
        }

        if ($webhook->disabled_at) {
            Log::info('Webhook is disabled, skipping delivery', [
                'webhook_id' => $this->webhookId,
                'event_id' => $this->eventId,
            ]);

            return;
        }

        try {
            $this->deliverWebhook($webhook);
        } catch (Exception $e) {
            Log::error('Webhook delivery failed', [
                'webhook_id' => $this->webhookId,
                'event_id' => $this->eventId,
                'attempt' => $this->attempt,
                'error' => $e->getMessage(),
            ]);

            // If this is the final attempt, disable the webhook
            if ($this->attempt >= $this->tries) {
                $this->handleFinalFailure($webhook);
            }

            throw $e;
        }
    }

    /**
     * Deliver webhook to the endpoint
     */
    private function deliverWebhook(Webhook $webhook): void
    {
        $signature = $this->createWebhookSignature($webhook->secret, $this->payload);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Shorts-Webhook/1.0',
            'X-Shorts-Signature' => $signature,
            'X-Shorts-Event' => $this->event,
            'X-Shorts-Event-Id' => $this->eventId,
            'X-Shorts-Webhook-Id' => $this->webhookId,
            'X-Shorts-Timestamp' => (string) time(),
        ];

        $response = Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->post($webhook->url, $this->payload);

        if (! $response->successful()) {
            throw new Exception(
                "Webhook delivery failed with status {$response->status()}: {$response->body()}"
            );
        }

        // Reset failure count on successful delivery
        $webhook->update([
            'failure_count' => 0,
            'last_success_at' => now(),
        ]);

        Log::info('Webhook delivered successfully', [
            'webhook_id' => $this->webhookId,
            'event_id' => $this->eventId,
            'status' => $response->status(),
        ]);
    }

    /**
     * Create webhook signature following dub-main patterns
     */
    private function createWebhookSignature(string $secret, array $payload): string
    {
        $timestamp = time();
        $body = json_encode($payload);
        $signaturePayload = $timestamp.'.'.$body;

        return 'v1='.hash_hmac('sha256', $signaturePayload, $secret);
    }

    /**
     * Handle final failure - disable webhook after max retries
     */
    private function handleFinalFailure(Webhook $webhook): void
    {
        $webhook->update([
            'failure_count' => $webhook->failure_count + 1,
            'disabled_at' => now(),
        ]);

        Log::warning('Webhook disabled after max failures', [
            'webhook_id' => $this->webhookId,
            'failure_count' => $webhook->failure_count,
        ]);

        // TODO: Send notification email to workspace owners
        // This would follow dub-main pattern from webhook/failure.ts
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Webhook job failed permanently', [
            'webhook_id' => $this->webhookId,
            'event_id' => $this->eventId,
            'error' => $exception->getMessage(),
        ]);
    }
}
