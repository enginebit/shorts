<?php

declare(strict_types=1);

namespace App\Services\Cache;

use App\Models\Link;
use Illuminate\Database\Eloquent\Collection;

/**
 * Link Cache Service
 *
 * Caches link data following dub-main patterns:
 * - 24-hour TTL for regular links
 * - No expiration for links with webhooks (expensive to fetch)
 * - Case-sensitive domain handling
 * - Pipeline operations for bulk operations
 */
final class LinkCacheService extends BaseCacheService
{
    // Following dub-main: 24 hours cache expiration
    public const CACHE_EXPIRATION = 60 * 60 * 24;

    public function __construct()
    {
        parent::__construct('linkcache', 'links', self::CACHE_EXPIRATION);
    }

    /**
     * Cache multiple links using pipeline (following dub-main mset pattern)
     */
    public function msetLinks(Collection $links): array
    {
        if ($links->isEmpty()) {
            return [];
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($links as $link) {
            $cacheKey = $this->createLinkKey($link->domain, $link->key);
            $redisLink = $this->formatRedisLink($link);

            // Following dub-main: no expiration for links with webhooks
            $hasWebhooks = $link->webhooks()->exists();

            if ($hasWebhooks) {
                $pipeline->set($cacheKey, json_encode($redisLink));
            } else {
                $pipeline->setex($cacheKey, self::CACHE_EXPIRATION, json_encode($redisLink));
            }
        }

        return $pipeline->exec();
    }

    /**
     * Cache single link
     */
    public function setLink(Link $link): bool
    {
        $cacheKey = $this->createLinkKey($link->domain, $link->key);
        $redisLink = $this->formatRedisLink($link);

        // Following dub-main: no expiration for links with webhooks
        $hasWebhooks = $link->webhooks()->exists();

        if ($hasWebhooks) {
            return $this->redis()->set($cacheKey, json_encode($redisLink));
        } else {
            return $this->redis()->setex($cacheKey, self::CACHE_EXPIRATION, json_encode($redisLink));
        }
    }

    /**
     * Get cached link
     */
    public function getLink(string $domain, string $key): ?array
    {
        // Following dub-main: use direct key format for retrieval
        $cacheKey = "linkcache:{$domain}:{$key}";
        $value = $this->redis()->get($cacheKey);

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Delete cached link
     */
    public function deleteLink(string $domain, string $key): int
    {
        $cacheKey = $this->createLinkKey($domain, $key);

        return $this->redis()->del($cacheKey);
    }

    /**
     * Delete multiple cached links
     */
    public function deleteManyLinks(array $links): array
    {
        if (empty($links)) {
            return [];
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($links as $link) {
            $cacheKey = $this->createLinkKey($link['domain'], $link['key']);
            $pipeline->del($cacheKey);
        }

        return $pipeline->exec();
    }

    /**
     * Expire multiple links immediately (for bulk updates)
     */
    public function expireManyLinks(array $links): array
    {
        if (empty($links)) {
            return [];
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($links as $link) {
            $cacheKey = $this->createLinkKey($link['domain'], $link['key']);
            $pipeline->expire($cacheKey, 1);
        }

        return $pipeline->exec();
    }

    /**
     * Create cache key for link (following dub-main case sensitivity logic)
     */
    private function createLinkKey(string $domain, string $key): string
    {
        // Following dub-main: handle case sensitivity based on domain
        $caseSensitive = $this->isCaseSensitiveDomain($domain);
        $originalKey = $caseSensitive ? $this->decodeKey($key) : $key;
        $cacheKey = "linkcache:{$domain}:{$originalKey}";

        return $caseSensitive ? $cacheKey : strtolower($cacheKey);
    }

    /**
     * Format link for Redis storage (following dub-main formatRedisLink)
     */
    private function formatRedisLink(Link $link): array
    {
        $formatted = [
            'id' => $link->id,
            'url' => $link->url,
            'domain' => $link->domain,
            'key' => $link->key,
            'created_at' => $link->created_at?->toISOString(),
            'updated_at' => $link->updated_at?->toISOString(),
        ];

        // Add optional fields only if they exist
        if ($link->title) {
            $formatted['title'] = $link->title;
        }

        if ($link->description) {
            $formatted['description'] = $link->description;
        }

        if ($link->image) {
            $formatted['image'] = $link->image;
        }

        if ($link->expires_at) {
            $formatted['expires_at'] = $link->expires_at->toISOString();
        }

        if ($link->password) {
            $formatted['password'] = true; // Don't store actual password
        }

        if ($link->proxy) {
            $formatted['proxy'] = true;
        }

        if ($link->rewrite) {
            $formatted['rewrite'] = true;
        }

        if ($link->ios_url) {
            $formatted['ios'] = $link->ios_url;
        }

        if ($link->android_url) {
            $formatted['android'] = $link->android_url;
        }

        if ($link->geo_targeting) {
            $formatted['geo'] = $link->geo_targeting;
        }

        if ($link->workspace_id) {
            $formatted['workspace_id'] = $link->workspace_id;
        }

        // Add webhook IDs if they exist
        $webhookIds = $link->webhooks()->pluck('id')->toArray();
        if (! empty($webhookIds)) {
            $formatted['webhook_ids'] = $webhookIds;
        }

        return $formatted;
    }

    /**
     * Check if domain is case sensitive (following dub-main logic)
     */
    private function isCaseSensitiveDomain(string $domain): bool
    {
        // Following dub-main: most domains are case-insensitive
        // Add specific case-sensitive domains here if needed
        $caseSensitiveDomains = [
            'git.new',
            'cal.link',
            // Add more as needed
        ];

        return in_array($domain, $caseSensitiveDomains);
    }

    /**
     * Decode key (following dub-main logic)
     */
    private function decodeKey(string $key): string
    {
        // Following dub-main: decode URL-encoded keys
        return urldecode($key);
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $info = $this->redis()->info('keyspace');
        $dbInfo = $info['db'.config('database.redis.links.database', '2')] ?? '';

        if (preg_match('/keys=(\d+)/', $dbInfo, $matches)) {
            $totalKeys = (int) $matches[1];
        } else {
            $totalKeys = 0;
        }

        return [
            'total_cached_links' => $totalKeys,
            'cache_prefix' => $this->keyPrefix,
            'connection' => $this->connection,
            'default_ttl' => $this->defaultTtl,
        ];
    }
}
