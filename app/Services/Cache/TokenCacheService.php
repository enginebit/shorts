<?php

declare(strict_types=1);

namespace App\Services\Cache;

/**
 * Token Cache Service
 *
 * Caches authentication tokens following dub-main patterns:
 * - JWT validation results
 * - API token information
 * - JWKS keys with TTL
 * - User session data
 */
final class TokenCacheService extends BaseCacheService
{
    // Following dub-main: 24 hours cache expiration
    public const CACHE_EXPIRATION = 60 * 60 * 24;

    // JWKS cache expiration (1 hour as configured in our Supabase service)
    public const JWKS_CACHE_EXPIRATION = 60 * 60;

    public function __construct()
    {
        parent::__construct('tokenCache', 'cache', self::CACHE_EXPIRATION);
    }

    /**
     * Cache API token information (following dub-main token-cache pattern)
     */
    public function setToken(string $hashedKey, array $tokenData): bool
    {
        $cacheData = [
            'scopes' => $tokenData['scopes'] ?? null,
            'rate_limit' => $tokenData['rate_limit'] ?? null,
            'workspace_id' => $tokenData['workspace_id'] ?? null,
            'expires' => $tokenData['expires'] ?? null,
            'user' => [
                'id' => $tokenData['user']['id'],
                'name' => $tokenData['user']['name'] ?? null,
                'email' => $tokenData['user']['email'] ?? null,
                'is_machine' => $tokenData['user']['is_machine'] ?? false,
            ],
        ];

        return $this->set($hashedKey, $cacheData);
    }

    /**
     * Get cached token information
     */
    public function getToken(string $hashedKey): ?array
    {
        return $this->get($hashedKey);
    }

    /**
     * Delete cached token
     */
    public function deleteToken(string $hashedKey): int
    {
        return $this->delete($hashedKey);
    }

    /**
     * Expire multiple tokens immediately (for token revocation)
     */
    public function expireTokens(array $hashedKeys): array
    {
        return $this->expireMany($hashedKeys);
    }

    /**
     * Cache JWKS keys (for Supabase JWT validation)
     */
    public function setJwks(string $keyId, array $jwksData): bool
    {
        $cacheKey = "jwks:{$keyId}";

        return $this->redis()->setex(
            $this->createKey($cacheKey),
            self::JWKS_CACHE_EXPIRATION,
            json_encode($jwksData)
        );
    }

    /**
     * Get cached JWKS key
     */
    public function getJwks(string $keyId): ?array
    {
        $cacheKey = "jwks:{$keyId}";
        $value = $this->redis()->get($this->createKey($cacheKey));

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Cache all JWKS keys from discovery endpoint
     */
    public function setAllJwks(array $jwksKeys): bool
    {
        if (empty($jwksKeys)) {
            return false;
        }

        $pipeline = $this->redis()->pipeline();

        foreach ($jwksKeys as $key) {
            if (isset($key['kid'])) {
                $cacheKey = $this->createKey("jwks:{$key['kid']}");
                $pipeline->setex($cacheKey, self::JWKS_CACHE_EXPIRATION, json_encode($key));
            }
        }

        $results = $pipeline->exec();

        return ! in_array(false, $results, true);
    }

    /**
     * Cache JWT validation result
     */
    public function setJwtValidation(string $jwtHash, array $validationResult, int $ttl = 300): bool
    {
        $cacheKey = "jwt_validation:{$jwtHash}";

        return $this->redis()->setex(
            $this->createKey($cacheKey),
            $ttl, // Short TTL for JWT validation (5 minutes)
            json_encode($validationResult)
        );
    }

    /**
     * Get cached JWT validation result
     */
    public function getJwtValidation(string $jwtHash): ?array
    {
        $cacheKey = "jwt_validation:{$jwtHash}";
        $value = $this->redis()->get($this->createKey($cacheKey));

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Cache user session data
     */
    public function setUserSession(string $sessionId, array $sessionData, int $ttl = 3600): bool
    {
        $cacheKey = "user_session:{$sessionId}";
        $result = $this->redis()->setex(
            $this->createKey($cacheKey),
            $ttl, // 1 hour TTL for sessions
            json_encode($sessionData)
        );

        // Handle Predis response
        return $result === true || (is_object($result) && method_exists($result, '__toString') && (string) $result === 'OK');
    }

    /**
     * Get cached user session
     */
    public function getUserSession(string $sessionId): ?array
    {
        $cacheKey = "user_session:{$sessionId}";
        $value = $this->redis()->get($this->createKey($cacheKey));

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Delete user session
     */
    public function deleteUserSession(string $sessionId): int
    {
        $cacheKey = "user_session:{$sessionId}";

        return $this->redis()->del($this->createKey($cacheKey));
    }

    /**
     * Cache workspace data for user (for quick access)
     */
    public function setUserWorkspaces(string $userId, array $workspaces, int $ttl = 1800): bool
    {
        $cacheKey = "user_workspaces:{$userId}";
        $result = $this->redis()->setex(
            $this->createKey($cacheKey),
            $ttl, // 30 minutes TTL
            json_encode($workspaces)
        );

        // Handle Predis response
        return $result === true || (is_object($result) && method_exists($result, '__toString') && (string) $result === 'OK');
    }

    /**
     * Get cached user workspaces
     */
    public function getUserWorkspaces(string $userId): ?array
    {
        $cacheKey = "user_workspaces:{$userId}";
        $value = $this->redis()->get($this->createKey($cacheKey));

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Invalidate user workspaces cache (when workspace membership changes)
     */
    public function invalidateUserWorkspaces(string $userId): int
    {
        $cacheKey = "user_workspaces:{$userId}";

        return $this->redis()->del($this->createKey($cacheKey));
    }

    /**
     * Cache rate limit information
     */
    public function setRateLimit(string $identifier, int $requests, int $windowSeconds): bool
    {
        $cacheKey = "ratelimit:{$identifier}";

        return $this->redis()->setex(
            $this->createKey($cacheKey),
            $windowSeconds,
            json_encode([
                'requests' => $requests,
                'window' => $windowSeconds,
                'reset_at' => time() + $windowSeconds,
            ])
        );
    }

    /**
     * Get rate limit information
     */
    public function getRateLimit(string $identifier): ?array
    {
        $cacheKey = "ratelimit:{$identifier}";
        $value = $this->redis()->get($this->createKey($cacheKey));

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Increment rate limit counter
     */
    public function incrementRateLimit(string $identifier): int
    {
        $cacheKey = "ratelimit:{$identifier}";

        return $this->redis()->incr($this->createKey($cacheKey));
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
            'total_cached_tokens' => $totalKeys,
            'cache_prefix' => $this->keyPrefix,
            'connection' => $this->connection,
            'default_ttl' => $this->defaultTtl,
            'jwks_ttl' => self::JWKS_CACHE_EXPIRATION,
        ];
    }
}
