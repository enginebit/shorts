<?php

declare(strict_types=1);

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Base Cache Service
 *
 * Provides common caching functionality following dub-main patterns
 */
abstract class BaseCacheService
{
    protected string $keyPrefix;

    protected string $connection;

    protected int $defaultTtl;

    public function __construct(string $keyPrefix, string $connection = 'default', int $defaultTtl = 86400)
    {
        $this->keyPrefix = $keyPrefix;
        $this->connection = $connection;
        $this->defaultTtl = $defaultTtl; // 24 hours default
    }

    /**
     * Create cache key with prefix
     */
    protected function createKey(string $key): string
    {
        return "{$this->keyPrefix}:{$key}";
    }

    /**
     * Get Redis connection
     */
    protected function redis()
    {
        return Redis::connection($this->connection);
    }

    /**
     * Get cache store
     */
    protected function cache()
    {
        return Cache::store($this->connection);
    }

    /**
     * Set cache value with TTL
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $result = $this->redis()->setex($this->createKey($key), $ttl, json_encode($value));

        // Handle Predis response (returns Status object) vs phpredis (returns boolean)
        return $result === true || (is_object($result) && method_exists($result, '__toString') && (string) $result === 'OK');
    }

    /**
     * Get cache value
     */
    public function get(string $key): mixed
    {
        $value = $this->redis()->get($this->createKey($key));

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Delete cache key
     */
    public function delete(string $key): int
    {
        return $this->redis()->del($this->createKey($key));
    }

    /**
     * Check if key exists
     */
    public function exists(string $key): bool
    {
        return (bool) $this->redis()->exists($this->createKey($key));
    }

    /**
     * Set multiple values using pipeline (following dub-main mset pattern)
     */
    public function mset(array $keyValuePairs, ?int $ttl = null): array
    {
        if (empty($keyValuePairs)) {
            return [];
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $pipeline = $this->redis()->pipeline();

        foreach ($keyValuePairs as $key => $value) {
            $pipeline->setex($this->createKey($key), $ttl, json_encode($value));
        }

        return $pipeline->exec();
    }

    /**
     * Get multiple values
     */
    public function mget(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $cacheKeys = array_map(fn ($key) => $this->createKey($key), $keys);
        $values = $this->redis()->mget($cacheKeys);

        $result = [];
        foreach ($keys as $index => $key) {
            $result[$key] = $values[$index] ? json_decode($values[$index], true) : null;
        }

        return $result;
    }

    /**
     * Delete multiple keys using pipeline
     */
    public function deleteMany(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($keys as $key) {
            $pipeline->del($this->createKey($key));
        }

        return $pipeline->exec();
    }

    /**
     * Expire multiple keys immediately (following dub-main expireMany pattern)
     */
    public function expireMany(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($keys as $key) {
            $pipeline->expire($this->createKey($key), 1);
        }

        return $pipeline->exec();
    }

    /**
     * Increment counter (for analytics)
     */
    public function increment(string $key, int $value = 1): int
    {
        return $this->redis()->incrby($this->createKey($key), $value);
    }

    /**
     * Add to sorted set with score (for analytics/metrics)
     */
    public function zadd(string $key, float $score, string $member): int
    {
        return $this->redis()->zadd($this->createKey($key), $score, $member);
    }

    /**
     * Increment score in sorted set
     */
    public function zincrby(string $key, float $increment, string $member): float
    {
        return $this->redis()->zincrby($this->createKey($key), $increment, $member);
    }

    /**
     * Get range from sorted set
     */
    public function zrange(string $key, int $start, int $stop, bool $withScores = false): array
    {
        return $this->redis()->zrange($this->createKey($key), $start, $stop, $withScores ? 'WITHSCORES' : null);
    }

    /**
     * Get reverse range from sorted set (highest scores first)
     */
    public function zrevrange(string $key, int $start, int $stop, bool $withScores = false): array
    {
        return $this->redis()->zrevrange($this->createKey($key), $start, $stop, $withScores ? 'WITHSCORES' : null);
    }
}
