<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseAuthService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupabaseAuthTest extends TestCase
{
    use RefreshDatabase;

    private SupabaseAuthService $supabaseAuth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supabaseAuth = app(SupabaseAuthService::class);
    }

    /** @test */
    public function it_can_verify_supabase_configuration()
    {
        $verification = $this->supabaseAuth->verifyConfiguration();

        $this->assertTrue($verification['configured'], 'Supabase should be properly configured');
        $this->assertEmpty($verification['issues'], 'There should be no configuration issues');
        $this->assertArrayHasKey('config', $verification);
        $this->assertEquals('https://yoqmmgxkbyuhcnvqvypw.supabase.co', $verification['config']['url']);
    }

    /** @test */
    public function it_can_access_public_health_endpoint()
    {
        $response = $this->getJson('/api/supabase/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'Supabase Auth API',
                'version' => '1.0.0',
            ]);
    }

    /** @test */
    public function it_rejects_requests_without_jwt_token()
    {
        $response = $this->getJson('/api/supabase/user');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Missing authentication token',
            ]);
    }

    /** @test */
    public function it_rejects_invalid_jwt_tokens()
    {
        $invalidToken = 'invalid.jwt.token';

        $response = $this->getJson('/api/supabase/user', [
            'Authorization' => "Bearer {$invalidToken}",
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid authentication token',
            ]);
    }

    /** @test */
    public function it_creates_user_from_valid_jwt_payload()
    {
        // Create a mock JWT payload (this would normally come from Supabase)
        $mockPayload = [
            'sub' => 'test-supabase-user-id',
            'email' => 'test@example.com',
            'role' => 'authenticated',
            'aal' => 'aal1',
            'session_id' => 'test-session-id',
            'is_anonymous' => false,
            'app_metadata' => ['provider' => 'email'],
            'user_metadata' => ['name' => 'Test User'],
            'amr' => [['method' => 'password', 'timestamp' => time()]],
            'iss' => config('supabase.jwt.issuer'),
            'aud' => config('supabase.jwt.audience'),
            'exp' => time() + 3600,
            'iat' => time(),
        ];

        // Extract user data from payload
        $userData = $this->supabaseAuth->extractUserFromPayload($mockPayload);

        $this->assertEquals('test-supabase-user-id', $userData['id']);
        $this->assertEquals('test@example.com', $userData['email']);
        $this->assertEquals('authenticated', $userData['role']);
        $this->assertEquals('aal1', $userData['aal']);
        $this->assertFalse($userData['is_anonymous']);
    }

    /** @test */
    public function it_can_find_existing_user_by_supabase_id()
    {
        // Create a user with Supabase ID
        $user = User::factory()->create([
            'supabase_id' => 'existing-supabase-id',
            'email' => 'existing@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'supabase_id' => 'existing-supabase-id',
            'email' => 'existing@example.com',
        ]);
    }

    /** @test */
    public function it_can_update_user_with_supabase_metadata()
    {
        $user = User::factory()->create([
            'supabase_id' => 'test-user-id',
            'email' => 'test@example.com',
        ]);

        $supabaseUserData = [
            'id' => 'test-user-id',
            'email' => 'test@example.com',
            'aal' => 'aal2',
            'session_id' => 'new-session-id',
            'is_anonymous' => false,
            'app_metadata' => ['provider' => 'google'],
            'user_metadata' => ['name' => 'Updated Name'],
            'amr' => [
                ['method' => 'password', 'timestamp' => time() - 100],
                ['method' => 'totp', 'timestamp' => time()],
            ],
        ];

        $user->updateFromSupabase($supabaseUserData);
        $user->refresh();

        $this->assertEquals('Updated Name', $user->name);
        $this->assertNotNull($user->supabase_metadata);
        $this->assertEquals('aal2', $user->supabase_metadata['aal']);
        $this->assertTrue($user->hasMfaEnabled());
    }

    /** @test */
    public function it_can_check_user_supabase_roles()
    {
        $user = User::factory()->create([
            'supabase_metadata' => [
                'app_metadata' => [
                    'role' => 'admin',
                ],
            ],
        ]);

        $this->assertTrue($user->hasSupabaseRole('admin'));
        $this->assertFalse($user->hasSupabaseRole('user'));
    }

    /** @test */
    public function it_can_detect_mfa_enabled_users()
    {
        // User with MFA (aal2)
        $mfaUser = User::factory()->create([
            'supabase_metadata' => [
                'aal' => 'aal2',
            ],
        ]);

        // User without MFA (aal1)
        $regularUser = User::factory()->create([
            'supabase_metadata' => [
                'aal' => 'aal1',
            ],
        ]);

        $this->assertTrue($mfaUser->hasMfaEnabled());
        $this->assertFalse($regularUser->hasMfaEnabled());
    }

    /** @test */
    public function it_can_create_service_client_headers()
    {
        $headers = $this->supabaseAuth->createServiceHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertArrayHasKey('apikey', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertStringStartsWith('Bearer ', $headers['Authorization']);
    }

    /** @test */
    public function it_validates_jwt_claims_properly()
    {
        // Test with valid claims
        $validPayload = [
            'iss' => config('supabase.jwt.issuer'),
            'aud' => config('supabase.jwt.audience'),
            'exp' => time() + 3600,
            'sub' => 'test-user-id',
            'role' => 'authenticated',
            'iat' => time(),
        ];

        // This would normally be called internally by validateToken
        $reflection = new \ReflectionClass($this->supabaseAuth);
        $method = $reflection->getMethod('validateClaims');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->supabaseAuth, $validPayload));

        // Test with invalid issuer
        $invalidPayload = $validPayload;
        $invalidPayload['iss'] = 'https://wrong-issuer.com';

        $this->assertFalse($method->invoke($this->supabaseAuth, $invalidPayload));

        // Test with expired token
        $expiredPayload = $validPayload;
        $expiredPayload['exp'] = time() - 3600;

        $this->assertFalse($method->invoke($this->supabaseAuth, $expiredPayload));
    }

    /** @test */
    public function it_handles_missing_required_claims()
    {
        $incompletePayload = [
            'iss' => config('supabase.jwt.issuer'),
            'aud' => config('supabase.jwt.audience'),
            // Missing 'exp', 'sub', 'role'
        ];

        $reflection = new \ReflectionClass($this->supabaseAuth);
        $method = $reflection->getMethod('validateClaims');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->supabaseAuth, $incompletePayload));
    }

    /** @test */
    public function it_handles_invalid_roles()
    {
        $invalidRolePayload = [
            'iss' => config('supabase.jwt.issuer'),
            'aud' => config('supabase.jwt.audience'),
            'exp' => time() + 3600,
            'sub' => 'test-user-id',
            'role' => 'invalid_role', // Not in allowed roles
            'iat' => time(),
        ];

        $reflection = new \ReflectionClass($this->supabaseAuth);
        $method = $reflection->getMethod('validateClaims');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->supabaseAuth, $invalidRolePayload));
    }
}
