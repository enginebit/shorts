<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Cache\TokenCacheService;
use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class SupabaseAuthService
{
    private Client $httpClient;

    private TokenCacheService $tokenCache;

    private string $projectUrl;

    private string $jwksUrl;

    private string $expectedIssuer;

    private string $expectedAudience;

    private array $allowedRoles;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => config('supabase.api.timeout', 30),
            'connect_timeout' => 10,
            'verify' => true,
        ]);

        $this->tokenCache = new TokenCacheService;
        $this->projectUrl = config('supabase.url');
        $this->jwksUrl = config('supabase.jwks_url');
        $this->expectedIssuer = config('supabase.jwt.issuer');
        $this->expectedAudience = config('supabase.jwt.audience');
        $this->allowedRoles = config('supabase.auth.allowed_roles', ['authenticated', 'anon']);
    }

    /**
     * Validate and decode a Supabase JWT token
     */
    public function validateToken(string $token): ?array
    {
        try {
            // Check cache for JWT validation result (following dub-main pattern)
            $jwtHash = hash('sha256', $token);
            $cachedResult = $this->tokenCache->getJwtValidation($jwtHash);

            if ($cachedResult !== null) {
                Log::debug('Supabase: JWT validation result retrieved from cache');

                return $cachedResult;
            }

            // Get JWKS (JSON Web Key Set) from Supabase
            $jwks = $this->getJWKS();

            if (empty($jwks['keys'])) {
                Log::error('Supabase: No keys found in JWKS response');

                return null;
            }

            // Convert JWKS to Key objects for firebase/php-jwt
            $keys = JWK::parseKeySet($jwks);

            // Decode and validate the JWT
            $decoded = JWT::decode($token, $keys);
            $payload = (array) $decoded;

            // Validate required claims
            if (! $this->validateClaims($payload)) {
                // Don't cache failed validations for security reasons
                return null;
            }

            Log::info('Supabase: JWT token validated successfully', [
                'user_id' => $payload['sub'] ?? 'unknown',
                'role' => $payload['role'] ?? 'unknown',
                'exp' => $payload['exp'] ?? 'unknown',
            ]);

            // Cache successful validation result (5 minutes TTL)
            $this->tokenCache->setJwtValidation($jwtHash, $payload, 300);

            return $payload;

        } catch (Exception $e) {
            Log::error('Supabase: JWT validation failed', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 50).'...',
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get JWKS from Supabase with caching and retry logic
     */
    private function getJWKS(): array
    {
        // Check cache first (following dub-main pattern)
        $projectRef = config('supabase.project_ref');
        $cachedJwks = $this->tokenCache->get("jwks_all:{$projectRef}");

        if ($cachedJwks !== null) {
            Log::debug('Supabase: JWKS retrieved from cache');

            return $cachedJwks;
        }

        // Fetch from Supabase if not cached
        $jwks = $this->fetchJWKSFromSupabase();

        if (! empty($jwks['keys'])) {
            // Cache the full JWKS response
            $this->tokenCache->set("jwks_all:{$projectRef}", $jwks, TokenCacheService::JWKS_CACHE_EXPIRATION);

            // Also cache individual keys for faster lookup
            $this->tokenCache->setAllJwks($jwks['keys']);
        }

        return $jwks;
    }

    /**
     * Fetch JWKS from Supabase with retry logic
     */
    private function fetchJWKSFromSupabase(): array
    {
        $retryAttempts = config('supabase.api.retry_attempts', 3);
        $retryDelay = config('supabase.api.retry_delay', 1000);

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                Log::debug("Supabase: Fetching JWKS (attempt {$attempt})", [
                    'url' => $this->jwksUrl,
                ]);

                $response = $this->httpClient->get($this->jwksUrl, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'User-Agent' => 'Laravel-Supabase-Auth/1.0',
                    ],
                ]);

                $jwks = json_decode($response->getBody()->getContents(), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON in JWKS response: '.json_last_error_msg());
                }

                if (! isset($jwks['keys']) || ! is_array($jwks['keys'])) {
                    throw new Exception('JWKS response missing keys array');
                }

                Log::info('Supabase: JWKS fetched successfully', [
                    'key_count' => count($jwks['keys']),
                    'attempt' => $attempt,
                ]);

                return $jwks;

            } catch (RequestException $e) {
                Log::warning("Supabase: JWKS fetch attempt {$attempt} failed", [
                    'url' => $this->jwksUrl,
                    'error' => $e->getMessage(),
                    'status_code' => $e->getResponse()?->getStatusCode(),
                ]);

                if ($attempt === $retryAttempts) {
                    throw new Exception("Failed to fetch JWKS after {$retryAttempts} attempts: ".$e->getMessage());
                }

                // Wait before retry
                usleep($retryDelay * 1000 * $attempt); // Exponential backoff
            }
        }

        throw new Exception('Unexpected error in JWKS fetching');
    }

    /**
     * Validate JWT claims according to Supabase requirements
     */
    private function validateClaims(array $payload): bool
    {
        // Check required fields
        $requiredFields = ['iss', 'aud', 'exp', 'sub', 'role'];
        foreach ($requiredFields as $field) {
            if (! isset($payload[$field])) {
                Log::warning("Supabase: Missing required JWT claim: {$field}");

                return false;
            }
        }

        // Validate issuer
        if ($payload['iss'] !== $this->expectedIssuer) {
            Log::warning('Supabase: Invalid JWT issuer', [
                'expected' => $this->expectedIssuer,
                'actual' => $payload['iss'],
            ]);

            return false;
        }

        // Validate audience (can be string or array)
        $audience = is_array($payload['aud']) ? $payload['aud'] : [$payload['aud']];
        if (! in_array($this->expectedAudience, $audience, true)) {
            Log::warning('Supabase: Invalid JWT audience', [
                'expected' => $this->expectedAudience,
                'actual' => $payload['aud'],
            ]);

            return false;
        }

        // Check expiration with 30-second leeway
        $now = time();
        $exp = (int) $payload['exp'];
        if ($exp < ($now - 30)) {
            Log::warning('Supabase: JWT token has expired', [
                'exp' => $exp,
                'now' => $now,
                'expired_by' => $now - $exp,
            ]);

            return false;
        }

        // Validate role
        if (! in_array($payload['role'], $this->allowedRoles, true)) {
            Log::warning('Supabase: Invalid JWT role', [
                'role' => $payload['role'],
                'allowed_roles' => $this->allowedRoles,
            ]);

            return false;
        }

        // Validate issued at time (not too far in the future)
        if (isset($payload['iat']) && $payload['iat'] > ($now + 300)) {
            Log::warning('Supabase: JWT issued too far in the future', [
                'iat' => $payload['iat'],
                'now' => $now,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Extract user information from validated JWT payload
     */
    public function extractUserFromPayload(array $payload): array
    {
        return [
            'id' => $payload['sub'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'role' => $payload['role'],
            'aal' => $payload['aal'] ?? 'aal1',
            'session_id' => $payload['session_id'] ?? null,
            'is_anonymous' => $payload['is_anonymous'] ?? false,
            'app_metadata' => $payload['app_metadata'] ?? [],
            'user_metadata' => $payload['user_metadata'] ?? [],
            'amr' => $payload['amr'] ?? [], // Authentication Method References
            'exp' => $payload['exp'],
            'iat' => $payload['iat'] ?? null,
            'iss' => $payload['iss'],
            'aud' => $payload['aud'],
        ];
    }

    /**
     * Create headers for Supabase API requests
     */
    public function createServiceHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.config('supabase.service_role_key'),
            'apikey' => config('supabase.service_role_key'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Laravel-Supabase-Auth/1.0',
        ];
    }

    /**
     * Create a configured HTTP client for Supabase API calls
     */
    public function createServiceClient(): Client
    {
        return new Client([
            'base_uri' => config('supabase.url'),
            'timeout' => config('supabase.api.timeout', 30),
            'headers' => $this->createServiceHeaders(),
            'verify' => true,
        ]);
    }

    /**
     * Verify if the service is properly configured
     */
    public function verifyConfiguration(): array
    {
        $issues = [];

        if (! config('supabase.url')) {
            $issues[] = 'SUPABASE_URL is not configured';
        }

        if (! config('supabase.anon_key')) {
            $issues[] = 'SUPABASE_ANON_KEY is not configured';
        }

        if (! config('supabase.service_role_key')) {
            $issues[] = 'SUPABASE_SERVICE_ROLE_KEY is not configured';
        }

        if (! config('supabase.jwt.issuer')) {
            $issues[] = 'JWT_ISSUER is not configured';
        }

        try {
            $jwks = $this->getJWKS();
            if (empty($jwks['keys'])) {
                $issues[] = 'JWKS endpoint returned no keys';
            }
        } catch (Exception $e) {
            $issues[] = 'Cannot fetch JWKS: '.$e->getMessage();
        }

        return [
            'configured' => empty($issues),
            'issues' => $issues,
            'config' => [
                'url' => config('supabase.url'),
                'project_ref' => config('supabase.project_ref'),
                'jwks_url' => config('supabase.jwks_url'),
                'issuer' => config('supabase.jwt.issuer'),
                'audience' => config('supabase.jwt.audience'),
            ],
        ];
    }
}
