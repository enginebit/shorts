<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear rate limiter state before each test
        RateLimiter::clear('auth');
        RateLimiter::clear('links');
        RateLimiter::clear('analytics');
        RateLimiter::clear('workspaces');
        RateLimiter::clear('password-reset');
    }

    public function test_authentication_rate_limiting(): void
    {
        // Make 5 requests (should be allowed)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);

            // Should not be rate limited yet
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'error' => 'Rate limit exceeded',
        ]);
    }

    public function test_password_reset_rate_limiting(): void
    {
        // Make 3 requests (should be allowed)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/auth/password/email', [
                'email' => 'test@example.com',
            ]);

            // Should not be rate limited yet
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // 4th request should be rate limited
        $response = $this->postJson('/api/auth/password/email', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'error',
            'message',
            'retry_after',
        ]);
    }

    public function test_authenticated_users_have_higher_limits(): void
    {
        $user = User::factory()->create();

        // Authenticated users should have much higher limits
        // Test with links API (100 requests per minute for authenticated users)
        $this->actingAs($user, 'sanctum');

        // Make 10 requests (well within the 100 limit)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/links');

            // Should not be rate limited
            $this->assertNotEquals(429, $response->getStatusCode());
        }
    }

    public function test_unauthenticated_users_have_lower_limits(): void
    {
        // Test with a public endpoint that has rate limiting
        // Use the general API rate limiter (60 requests per minute for unauthenticated)

        // Make 60 requests to a public endpoint
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/supabase/health');

            // Should not be rate limited yet (exactly at the limit)
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // 61st request should be rate limited
        $response = $this->getJson('/api/supabase/health');
        $response->assertStatus(429);
    }

    public function test_different_endpoints_have_different_limits(): void
    {
        // Test that analytics endpoints have different limits than auth endpoints

        // Make 5 auth requests (should hit auth limit)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
        }

        // Auth should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $response->assertStatus(429);

        // But analytics should still work (different rate limiter)
        $response = $this->getJson('/api/analytics/overview');
        $this->assertNotEquals(429, $response->getStatusCode());
    }

    public function test_rate_limit_headers_are_present(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Check that rate limit headers are present
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }

    public function test_rate_limit_error_page_for_web_requests(): void
    {
        // Make requests to exceed rate limit
        for ($i = 0; $i < 6; $i++) {
            $this->post('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
        }

        // Web request should get HTML error page
        $response = $this->post('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(429);

        // Should contain the error page content
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'application/json')) {
            $response->assertSee('Rate Limit Exceeded');
        }
    }

    public function test_rate_limiter_key_differentiation(): void
    {
        // Test that different rate limiters work independently

        // Exhaust auth rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
        }

        // Auth should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $response->assertStatus(429);

        // But general API endpoints should still work (different rate limiter)
        $response = $this->getJson('/api/supabase/health');

        $this->assertNotEquals(429, $response->getStatusCode());
    }
}
