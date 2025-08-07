<?php

declare(strict_types=1);

namespace App\Services\Cache;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;

/**
 * Webhook Cache Service
 *
 * Caches webhook data following dub-main patterns:
 * - No TTL for webhooks (permanent cache)
 * - Link-level webhook caching
 * - Bulk operations support
 */
final class WebhookCacheService extends BaseCacheService
{
    public function __construct()
    {
        // Following dub-main: no TTL for webhooks (permanent cache)
        parent::__construct('webhook', 'cache', 0);
    }

    /**
     * Cache multiple webhooks using pipeline (following dub-main mset pattern)
     */
    public function msetWebhooks(Collection $webhooks): array
    {
        if ($webhooks->isEmpty()) {
            return [];
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($webhooks as $webhook) {
            $cacheKey = $this->createKey($webhook->id);
            $formattedWebhook = $this->formatWebhook($webhook);
            $pipeline->set($cacheKey, json_encode($formattedWebhook));
        }

        return $pipeline->exec();
    }

    /**
     * Cache single webhook
     */
    public function setWebhook(Webhook $webhook): bool
    {
        // Following dub-main: only cache link-level webhooks
        if (! $this->isLinkLevelWebhook($webhook)) {
            return false;
        }

        $cacheKey = $this->createKey($webhook->id);
        $formattedWebhook = $this->formatWebhook($webhook);

        return $this->redis()->set($cacheKey, json_encode($formattedWebhook));
    }

    /**
     * Get multiple webhooks by IDs
     */
    public function mgetWebhooks(array $webhookIds): array
    {
        if (empty($webhookIds)) {
            return [];
        }

        $cacheKeys = array_map(fn ($id) => $this->createKey($id), $webhookIds);
        $values = $this->redis()->mget($cacheKeys);

        $webhooks = [];
        foreach ($values as $value) {
            if ($value) {
                $webhooks[] = json_decode($value, true);
            }
        }

        return $webhooks;
    }

    /**
     * Get single webhook by ID
     */
    public function getWebhook(string $webhookId): ?array
    {
        $value = $this->redis()->get($this->createKey($webhookId));

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Delete webhook from cache
     */
    public function deleteWebhook(string $webhookId): int
    {
        return $this->redis()->del($this->createKey($webhookId));
    }

    /**
     * Delete multiple webhooks from cache
     */
    public function deleteManyWebhooks(array $webhookIds): array
    {
        if (empty($webhookIds)) {
            return [];
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($webhookIds as $webhookId) {
            $pipeline->del($this->createKey($webhookId));
        }

        return $pipeline->exec();
    }

    /**
     * Get webhooks for a specific link
     */
    public function getWebhooksForLink(string $linkId): array
    {
        // This would require a reverse lookup - in practice, we'd store link->webhook mappings
        // For now, we'll implement a simple pattern-based search
        $pattern = $this->createKey('*');
        $keys = $this->redis()->keys($pattern);

        $linkWebhooks = [];
        foreach ($keys as $key) {
            $webhook = $this->redis()->get($key);
            if ($webhook) {
                $webhookData = json_decode($webhook, true);
                // Check if webhook is associated with the link (this would be more efficient with proper indexing)
                if (isset($webhookData['link_id']) && $webhookData['link_id'] === $linkId) {
                    $linkWebhooks[] = $webhookData;
                }
            }
        }

        return $linkWebhooks;
    }

    /**
     * Cache webhook failure information
     */
    public function recordWebhookFailure(string $webhookId, array $failureData): bool
    {
        $cacheKey = "webhook_failure:{$webhookId}:".time();

        return $this->redis()->setex(
            $this->createKey($cacheKey),
            60 * 60 * 24 * 7, // Keep failure data for 7 days
            json_encode($failureData)
        );
    }

    /**
     * Get webhook failure history
     */
    public function getWebhookFailures(string $webhookId, int $limit = 50): array
    {
        $pattern = $this->createKey("webhook_failure:{$webhookId}:*");
        $keys = $this->redis()->keys($pattern);

        // Sort keys by timestamp (descending)
        rsort($keys);
        $keys = array_slice($keys, 0, $limit);

        $failures = [];
        foreach ($keys as $key) {
            $failure = $this->redis()->get($key);
            if ($failure) {
                $failures[] = json_decode($failure, true);
            }
        }

        return $failures;
    }

    /**
     * Cache webhook delivery statistics
     */
    public function recordWebhookDelivery(string $webhookId, bool $success, int $responseTime): float
    {
        $date = date('Y-m-d');
        $successKey = "webhook_stats:{$webhookId}:success:{$date}";
        $totalKey = "webhook_stats:{$webhookId}:total:{$date}";
        $responseTimeKey = "webhook_stats:{$webhookId}:response_time:{$date}";

        $pipeline = $this->redis()->pipeline();

        if ($success) {
            $pipeline->incr($this->createKey($successKey));
        }
        $pipeline->incr($this->createKey($totalKey));
        $pipeline->lpush($this->createKey($responseTimeKey), $responseTime);

        // Keep only last 100 response times
        $pipeline->ltrim($this->createKey($responseTimeKey), 0, 99);

        // Set expiration for stats (30 days)
        $pipeline->expire($this->createKey($successKey), 60 * 60 * 24 * 30);
        $pipeline->expire($this->createKey($totalKey), 60 * 60 * 24 * 30);
        $pipeline->expire($this->createKey($responseTimeKey), 60 * 60 * 24 * 30);

        $results = $pipeline->exec();

        return $results[1] ?? 0; // Return total count
    }

    /**
     * Get webhook delivery statistics
     */
    public function getWebhookStats(string $webhookId, string $date): array
    {
        $successKey = "webhook_stats:{$webhookId}:success:{$date}";
        $totalKey = "webhook_stats:{$webhookId}:total:{$date}";
        $responseTimeKey = "webhook_stats:{$webhookId}:response_time:{$date}";

        $pipeline = $this->redis()->pipeline();
        $pipeline->get($this->createKey($successKey));
        $pipeline->get($this->createKey($totalKey));
        $pipeline->lrange($this->createKey($responseTimeKey), 0, -1);

        $results = $pipeline->exec();

        $successful = (int) ($results[0] ?? 0);
        $total = (int) ($results[1] ?? 0);
        $responseTimes = array_map('intval', $results[2] ?? []);

        $avgResponseTime = ! empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : 0;

        return [
            'successful' => $successful,
            'total' => $total,
            'failed' => $total - $successful,
            'success_rate' => $total > 0 ? ($successful / $total) * 100 : 0,
            'avg_response_time' => round($avgResponseTime, 2),
            'response_times' => $responseTimes,
        ];
    }

    /**
     * Format webhook for cache storage (following dub-main _format pattern)
     */
    private function formatWebhook(Webhook $webhook): array
    {
        $formatted = [
            'id' => $webhook->id,
            'url' => $webhook->url,
            'secret' => $webhook->secret,
            'triggers' => $webhook->triggers,
        ];

        // Add optional fields
        if ($webhook->disabled_at) {
            $formatted['disabled_at'] = $webhook->disabled_at->toISOString();
        }

        if ($webhook->workspace_id) {
            $formatted['workspace_id'] = $webhook->workspace_id;
        }

        if ($webhook->name) {
            $formatted['name'] = $webhook->name;
        }

        return $formatted;
    }

    /**
     * Check if webhook is link-level (following dub-main isLinkLevelWebhook)
     */
    private function isLinkLevelWebhook(Webhook $webhook): bool
    {
        // Following dub-main: only cache link-level webhooks for performance
        // This would depend on your webhook implementation
        // For now, we'll cache all webhooks
        return true;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $info = $this->redis()->info('keyspace');
        $dbInfo = $info['db'.config('database.redis.cache.database', '1')] ?? '';

        if (preg_match('/keys=(\d+)/', $dbInfo, $matches)) {
            $totalKeys = (int) $matches[1];
        } else {
            $totalKeys = 0;
        }

        return [
            'total_cached_webhooks' => $totalKeys,
            'cache_prefix' => $this->keyPrefix,
            'connection' => $this->connection,
            'permanent_cache' => true, // No TTL
        ];
    }
}
