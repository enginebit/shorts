<?php

declare(strict_types=1);

namespace App\Services\Cache;

/**
 * Analytics Cache Service
 *
 * Caches analytics data and metrics following dub-main patterns:
 * - Click tracking data
 * - Domain verification status
 * - Metatags generation tracking
 * - Analytics aggregations
 */
final class AnalyticsCacheService extends BaseCacheService
{
    // Following dub-main: 7 days cache expiration for analytics
    public const ANALYTICS_CACHE_EXPIRATION = 60 * 60 * 24 * 7;

    // Domain verification cache (1 hour)
    public const DOMAIN_CACHE_EXPIRATION = 60 * 60;

    public function __construct()
    {
        parent::__construct('analyticsCache', 'analytics', self::ANALYTICS_CACHE_EXPIRATION);
    }

    /**
     * Cache domain verification status (following dub-main allowed-hostnames-cache)
     */
    public function setDomainVerification(string $domain, array $verificationData): bool
    {
        $cacheKey = "domain_verification:{$domain}";

        return $this->redis()->setex(
            $this->createKey($cacheKey),
            self::DOMAIN_CACHE_EXPIRATION,
            json_encode($verificationData)
        );
    }

    /**
     * Get cached domain verification status
     */
    public function getDomainVerification(string $domain): ?array
    {
        $cacheKey = "domain_verification:{$domain}";
        $value = $this->redis()->get($this->createKey($cacheKey));

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Cache multiple domain verifications
     */
    public function setMultipleDomainVerifications(array $domains, array $verificationData): array
    {
        if (empty($domains)) {
            return [];
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($domains as $domain) {
            $cacheKey = $this->createKey("domain_verification:{$domain}");
            $pipeline->setex($cacheKey, self::DOMAIN_CACHE_EXPIRATION, json_encode($verificationData));
        }

        return $pipeline->exec();
    }

    /**
     * Delete domain verification cache
     */
    public function deleteDomainVerification(string $domain): int
    {
        $cacheKey = "domain_verification:{$domain}";

        return $this->redis()->del($this->createKey($cacheKey));
    }

    /**
     * Delete multiple domain verifications
     */
    public function deleteMultipleDomainVerifications(array $domains): array
    {
        if (empty($domains)) {
            return [];
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($domains as $domain) {
            $cacheKey = $this->createKey("domain_verification:{$domain}");
            $pipeline->del($cacheKey);
        }

        return $pipeline->exec();
    }

    /**
     * Record metatags generation (following dub-main record-metatags pattern)
     */
    public function recordMetatags(string $url, bool $error = false): float
    {
        // Skip default URL like dub-main
        if ($url === 'https://github.com/dubinc/dub') {
            return 0;
        }

        if ($error) {
            return $this->zincrby('metatags-error-zset', 1, $url);
        }

        // Extract domain for successful metatags
        $domain = $this->getDomainWithoutWWW($url);

        return $this->zincrby('metatags-zset', 1, $domain);
    }

    /**
     * Get metatags statistics
     */
    public function getMetatagsStats(int $limit = 100): array
    {
        $successful = $this->zrevrange('metatags-zset', 0, $limit - 1, true);
        $errors = $this->zrevrange('metatags-error-zset', 0, $limit - 1, true);

        return [
            'successful' => $successful,
            'errors' => $errors,
        ];
    }

    /**
     * Cache click data for link
     */
    public function recordClick(string $linkId, array $clickData): bool
    {
        $cacheKey = "click_data:{$linkId}:".date('Y-m-d-H');

        // Store click data with hourly granularity
        return $this->redis()->lpush($this->createKey($cacheKey), json_encode($clickData));
    }

    /**
     * Get click data for link
     */
    public function getClickData(string $linkId, string $date, ?string $hour = null): array
    {
        if ($hour) {
            $cacheKey = "click_data:{$linkId}:{$date}-{$hour}";
            $data = $this->redis()->lrange($this->createKey($cacheKey), 0, -1);
        } else {
            // Get all hours for the date
            $pattern = $this->createKey("click_data:{$linkId}:{$date}-*");
            $keys = $this->redis()->keys($pattern);
            $data = [];

            foreach ($keys as $key) {
                $hourData = $this->redis()->lrange($key, 0, -1);
                $data = array_merge($data, $hourData);
            }
        }

        return array_map(fn ($item) => json_decode($item, true), $data);
    }

    /**
     * Cache analytics aggregation
     */
    public function setAnalyticsAggregation(string $key, array $data, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? self::ANALYTICS_CACHE_EXPIRATION;
        $cacheKey = "analytics_agg:{$key}";

        return $this->redis()->setex(
            $this->createKey($cacheKey),
            $ttl,
            json_encode($data)
        );
    }

    /**
     * Get cached analytics aggregation
     */
    public function getAnalyticsAggregation(string $key): ?array
    {
        $cacheKey = "analytics_agg:{$key}";
        $value = $this->redis()->get($this->createKey($cacheKey));

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Increment workspace usage counter
     */
    public function incrementWorkspaceUsage(string $workspaceId, string $metric = 'links_created'): int
    {
        $cacheKey = "workspace_usage:{$workspaceId}:{$metric}:".date('Y-m');

        return $this->increment($cacheKey);
    }

    /**
     * Get workspace usage for current month
     */
    public function getWorkspaceUsage(string $workspaceId, string $metric = 'links_created'): int
    {
        $cacheKey = "workspace_usage:{$workspaceId}:{$metric}:".date('Y-m');
        $value = $this->redis()->get($this->createKey($cacheKey));

        return $value ? (int) $value : 0;
    }

    /**
     * Cache popular links (top clicked links)
     */
    public function recordLinkClick(string $linkId): float
    {
        $cacheKey = 'popular_links:'.date('Y-m-d');

        return $this->zincrby($cacheKey, 1, $linkId);
    }

    /**
     * Get popular links for date
     */
    public function getPopularLinks(string $date, int $limit = 50): array
    {
        $cacheKey = "popular_links:{$date}";

        return $this->zrevrange($cacheKey, 0, $limit - 1, true);
    }

    /**
     * Cache referrer data
     */
    public function recordReferrer(string $referrer): float
    {
        $domain = $this->getDomainWithoutWWW($referrer);
        $cacheKey = 'referrers:'.date('Y-m-d');

        return $this->zincrby($cacheKey, 1, $domain);
    }

    /**
     * Get top referrers for date
     */
    public function getTopReferrers(string $date, int $limit = 50): array
    {
        $cacheKey = "referrers:{$date}";

        return $this->zrevrange($cacheKey, 0, $limit - 1, true);
    }

    /**
     * Cache country/geo data
     */
    public function recordCountry(string $country): float
    {
        $cacheKey = 'countries:'.date('Y-m-d');

        return $this->zincrby($cacheKey, 1, $country);
    }

    /**
     * Get top countries for date
     */
    public function getTopCountries(string $date, int $limit = 50): array
    {
        $cacheKey = "countries:{$date}";

        return $this->zrevrange($cacheKey, 0, $limit - 1, true);
    }

    /**
     * Extract domain without www (following dub-main getDomainWithoutWWW)
     */
    private function getDomainWithoutWWW(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? $url;

        // Remove www. prefix
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $info = $this->redis()->info('keyspace');
        $dbInfo = $info['db'.config('database.redis.analytics.database', '4')] ?? '';

        if (preg_match('/keys=(\d+)/', $dbInfo, $matches)) {
            $totalKeys = (int) $matches[1];
        } else {
            $totalKeys = 0;
        }

        return [
            'total_analytics_keys' => $totalKeys,
            'cache_prefix' => $this->keyPrefix,
            'connection' => $this->connection,
            'default_ttl' => $this->defaultTtl,
            'domain_ttl' => self::DOMAIN_CACHE_EXPIRATION,
        ];
    }
}
