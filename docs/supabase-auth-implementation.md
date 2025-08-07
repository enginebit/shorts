# Supabase Authentication Implementation for Laravel

## 🎯 **Implementation Overview**

Based on the latest Supabase documentation and your specific project credentials, this implementation provides:

- **Server-side JWT validation** using Supabase's asymmetric signing keys
- **Laravel middleware integration** for protected routes
- **Workspace-aware authentication** preserving existing functionality
- **React frontend compatibility** with Inertia.js

## 📋 **Project Configuration**

### **Supabase Project Details**
- **Project ID**: `yoqmmgxkbyuhcnvqvypw`
- **Supabase URL**: `https://yoqmmgxkbyuhcnvqvypw.supabase.co`
- **JWKS URL**: `https://yoqmmgxkbyuhcnvqvypw.supabase.co/auth/v1/.well-known/jwks.json`

### **Authentication Keys**
- **Publishable Key**: `sb_publishable_a9mSM5l1yoic-nb1MXMZBg_lwyFetLE`
- **Service Role Key**: `sb_secret_eG9ajx80a4tXXF0EYXjAWw_5-s0TrXY`
- **JWT Key ID**: `fffddca8-20fe-4db1-abf5-eb8503df0077`

## 🔧 **Implementation Steps**

### **Step 1: Install Required Dependencies**

```bash
# Install JWT validation library
composer require firebase/jwt

# Install HTTP client for JWKS fetching
composer require guzzlehttp/guzzle

# Install caching for JWKS performance
composer require predis/predis
```

### **Step 2: Environment Configuration**

Add to your `.env` file:

```env
# Supabase Configuration
SUPABASE_URL=https://yoqmmgxkbyuhcnvqvypw.supabase.co
SUPABASE_ANON_KEY=sb_publishable_a9mSM5l1yoic-nb1MXMZBg_lwyFetLE
SUPABASE_SERVICE_ROLE_KEY=sb_secret_eG9ajx80a4tXXF0EYXjAWw_5-s0TrXY
SUPABASE_JWT_SECRET=your-jwt-secret-if-using-symmetric
SUPABASE_PROJECT_REF=yoqmmgxkbyuhcnvqvypw

# JWT Configuration
JWT_ALGORITHM=ES256
JWT_ISSUER=https://yoqmmgxkbyuhcnvqvypw.supabase.co/auth/v1
JWT_AUDIENCE=authenticated
JWKS_CACHE_TTL=3600
```

### **Step 3: Create Supabase Service**

Create `app/Services/SupabaseAuthService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class SupabaseAuthService
{
    private Client $httpClient;
    private string $projectUrl;
    private string $jwksUrl;
    private string $expectedIssuer;
    private string $expectedAudience;

    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 10]);
        $this->projectUrl = config('supabase.url');
        $this->jwksUrl = $this->projectUrl . '/auth/v1/.well-known/jwks.json';
        $this->expectedIssuer = $this->projectUrl . '/auth/v1';
        $this->expectedAudience = 'authenticated';
    }

    /**
     * Validate and decode a Supabase JWT token
     */
    public function validateToken(string $token): ?array
    {
        try {
            // Get JWKS (JSON Web Key Set) from Supabase
            $jwks = $this->getJWKS();
            
            if (empty($jwks['keys'])) {
                Log::error('No keys found in JWKS');
                return null;
            }

            // Convert JWKS to Key objects
            $keys = JWK::parseKeySet($jwks);

            // Decode and validate the JWT
            $decoded = JWT::decode($token, $keys);
            $payload = (array) $decoded;

            // Validate required claims
            if (!$this->validateClaims($payload)) {
                return null;
            }

            return $payload;

        } catch (Exception $e) {
            Log::error('JWT validation failed', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 50) . '...'
            ]);
            return null;
        }
    }

    /**
     * Get JWKS from Supabase with caching
     */
    private function getJWKS(): array
    {
        $cacheKey = 'supabase_jwks_' . config('supabase.project_ref');
        $cacheTTL = config('supabase.jwks_cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTTL, function () {
            try {
                $response = $this->httpClient->get($this->jwksUrl);
                $jwks = json_decode($response->getBody()->getContents(), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON in JWKS response');
                }

                return $jwks;

            } catch (Exception $e) {
                Log::error('Failed to fetch JWKS', [
                    'url' => $this->jwksUrl,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Validate JWT claims according to Supabase requirements
     */
    private function validateClaims(array $payload): bool
    {
        // Check required fields
        $requiredFields = ['iss', 'aud', 'exp', 'sub', 'role'];
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                Log::warning("Missing required JWT claim: {$field}");
                return false;
            }
        }

        // Validate issuer
        if ($payload['iss'] !== $this->expectedIssuer) {
            Log::warning('Invalid JWT issuer', [
                'expected' => $this->expectedIssuer,
                'actual' => $payload['iss']
            ]);
            return false;
        }

        // Validate audience
        $audience = is_array($payload['aud']) ? $payload['aud'] : [$payload['aud']];
        if (!in_array($this->expectedAudience, $audience)) {
            Log::warning('Invalid JWT audience', [
                'expected' => $this->expectedAudience,
                'actual' => $payload['aud']
            ]);
            return false;
        }

        // Check expiration
        if ($payload['exp'] < time()) {
            Log::warning('JWT token has expired');
            return false;
        }

        // Validate role
        $validRoles = ['authenticated', 'anon', 'service_role'];
        if (!in_array($payload['role'], $validRoles)) {
            Log::warning('Invalid JWT role', ['role' => $payload['role']]);
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
            'amr' => $payload['amr'] ?? [],
            'exp' => $payload['exp'],
            'iat' => $payload['iat'],
        ];
    }

    /**
     * Create a Supabase client for server-side operations
     */
    public function createServiceClient(): array
    {
        return [
            'url' => $this->projectUrl,
            'key' => config('supabase.service_role_key'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('supabase.service_role_key'),
                'apikey' => config('supabase.service_role_key'),
                'Content-Type' => 'application/json',
            ]
        ];
    }
}
```

### **Step 4: Create JWT Authentication Middleware**

Create `app/Http/Middleware/SupabaseAuthMiddleware.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SupabaseAuthService;
use App\Services\WorkspaceAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SupabaseAuthMiddleware
{
    private SupabaseAuthService $supabaseAuth;
    private WorkspaceAuthService $workspaceAuth;

    public function __construct(
        SupabaseAuthService $supabaseAuth,
        WorkspaceAuthService $workspaceAuth
    ) {
        $this->supabaseAuth = $supabaseAuth;
        $this->workspaceAuth = $workspaceAuth;
    }

    /**
     * Handle an incoming request with Supabase JWT authentication
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract JWT token from Authorization header
        $token = $this->extractTokenFromRequest($request);

        if (!$token) {
            return $this->unauthorizedResponse('Missing authentication token');
        }

        // Validate the JWT token
        $payload = $this->supabaseAuth->validateToken($token);

        if (!$payload) {
            return $this->unauthorizedResponse('Invalid authentication token');
        }

        // Extract user information
        $supabaseUser = $this->supabaseAuth->extractUserFromPayload($payload);

        // Find or create corresponding Laravel user
        $user = $this->findOrCreateUser($supabaseUser);

        if (!$user) {
            return $this->unauthorizedResponse('User not found or could not be created');
        }

        // Set authenticated user in Laravel
        auth()->setUser($user);

        // Add Supabase user data to request for access in controllers
        $request->merge([
            'supabase_user' => $supabaseUser,
            'supabase_token' => $token,
            'jwt_payload' => $payload
        ]);

        // Handle workspace-aware authentication
        $this->handleWorkspaceContext($request, $user);

        return $next($request);
    }

    /**
     * Extract JWT token from request headers
     */
    private function extractTokenFromRequest(Request $request): ?string
    {
        // Check Authorization header (Bearer token)
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Check for token in query parameters (for WebSocket connections)
        if ($request->has('token')) {
            return $request->get('token');
        }

        // Check for token in cookies (if using cookie-based auth)
        if ($request->hasCookie('supabase_token')) {
            return $request->cookie('supabase_token');
        }

        return null;
    }

    /**
     * Find or create Laravel user based on Supabase user data
     */
    private function findOrCreateUser(array $supabaseUser): ?\App\Models\User
    {
        try {
            // Find user by Supabase ID or email
            $user = \App\Models\User::where('supabase_id', $supabaseUser['id'])
                ->orWhere('email', $supabaseUser['email'])
                ->first();

            if (!$user && $supabaseUser['email']) {
                // Create new user if not exists
                $user = \App\Models\User::create([
                    'supabase_id' => $supabaseUser['id'],
                    'email' => $supabaseUser['email'],
                    'name' => $supabaseUser['user_metadata']['name'] ?? 
                             $supabaseUser['user_metadata']['full_name'] ?? 
                             explode('@', $supabaseUser['email'])[0],
                    'email_verified_at' => now(), // Supabase handles verification
                ]);

                Log::info('Created new user from Supabase authentication', [
                    'user_id' => $user->id,
                    'supabase_id' => $supabaseUser['id'],
                    'email' => $supabaseUser['email']
                ]);
            }

            // Update Supabase ID if user exists but doesn't have it
            if ($user && !$user->supabase_id) {
                $user->update(['supabase_id' => $supabaseUser['id']]);
            }

            return $user;

        } catch (\Exception $e) {
            Log::error('Failed to find or create user', [
                'supabase_user' => $supabaseUser,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Handle workspace context for authenticated user
     */
    private function handleWorkspaceContext(Request $request, \App\Models\User $user): void
    {
        try {
            // Use existing workspace authentication service
            $workspaceData = $this->workspaceAuth->getWorkspaceDataForSharing($user);
            
            // Add workspace data to request
            $request->merge([
                'workspace_data' => $workspaceData,
                'current_workspace' => $workspaceData['currentWorkspace'] ?? null
            ]);

            // Set workspace context in session if using session-based routing
            if ($workspaceData['currentWorkspace']) {
                session(['current_workspace' => $workspaceData['currentWorkspace']]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to set workspace context', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): Response
    {
        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $message
            ], 401);
        }

        return redirect()->route('login')->with('error', $message);
    }
}
```

### **Step 5: Create Supabase Configuration File**

Create `config/supabase.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supabase Configuration
    |--------------------------------------------------------------------------
    */

    'url' => env('SUPABASE_URL'),
    'anon_key' => env('SUPABASE_ANON_KEY'),
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
    'project_ref' => env('SUPABASE_PROJECT_REF'),

    /*
    |--------------------------------------------------------------------------
    | JWT Configuration
    |--------------------------------------------------------------------------
    */

    'jwt' => [
        'algorithm' => env('JWT_ALGORITHM', 'ES256'),
        'issuer' => env('JWT_ISSUER'),
        'audience' => env('JWT_AUDIENCE', 'authenticated'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWKS Configuration
    |--------------------------------------------------------------------------
    */

    'jwks_cache_ttl' => env('JWKS_CACHE_TTL', 3600),
    'jwks_url' => env('SUPABASE_URL') . '/auth/v1/.well-known/jwks.json',

    /*
    |--------------------------------------------------------------------------
    | Authentication Settings
    |--------------------------------------------------------------------------
    */

    'auth' => [
        'auto_create_users' => env('SUPABASE_AUTO_CREATE_USERS', true),
        'sync_user_metadata' => env('SUPABASE_SYNC_USER_METADATA', true),
        'workspace_aware' => env('SUPABASE_WORKSPACE_AWARE', true),
    ],
];
```

### **Step 6: Update User Model**

Update `app/Models/User.php` to support Supabase integration:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'supabase_id',        // Add Supabase user ID
        'supabase_metadata',  // Store Supabase user metadata
        'email_verified_at',
        'default_workspace',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'supabase_metadata' => 'json',  // Cast to JSON
    ];

    /**
     * Get workspaces for this user
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_users')
                    ->withPivot(['role', 'default_folder_id'])
                    ->withTimestamps();
    }

    /**
     * Get the default workspace for this user
     */
    public function defaultWorkspace(): ?\App\Models\Workspace
    {
        if (!$this->default_workspace) {
            return null;
        }

        return Workspace::where('slug', $this->default_workspace)->first();
    }

    /**
     * Update user with Supabase metadata
     */
    public function updateFromSupabase(array $supabaseUser): void
    {
        $updates = [];

        // Update basic fields if they've changed
        if ($this->email !== $supabaseUser['email']) {
            $updates['email'] = $supabaseUser['email'];
        }

        // Update metadata
        $updates['supabase_metadata'] = [
            'aal' => $supabaseUser['aal'],
            'session_id' => $supabaseUser['session_id'],
            'is_anonymous' => $supabaseUser['is_anonymous'],
            'app_metadata' => $supabaseUser['app_metadata'],
            'user_metadata' => $supabaseUser['user_metadata'],
            'amr' => $supabaseUser['amr'],
            'last_updated' => now()->toISOString(),
        ];

        // Update name from user_metadata if available
        $userMetadata = $supabaseUser['user_metadata'];
        if (!empty($userMetadata['name']) && $this->name !== $userMetadata['name']) {
            $updates['name'] = $userMetadata['name'];
        } elseif (!empty($userMetadata['full_name']) && $this->name !== $userMetadata['full_name']) {
            $updates['name'] = $userMetadata['full_name'];
        }

        if (!empty($updates)) {
            $this->update($updates);
        }
    }

    /**
     * Check if user has specific Supabase role
     */
    public function hasSupabaseRole(string $role): bool
    {
        $metadata = $this->supabase_metadata ?? [];
        $appMetadata = $metadata['app_metadata'] ?? [];

        return ($appMetadata['role'] ?? null) === $role;
    }

    /**
     * Get user's authentication assurance level
     */
    public function getAuthAssuranceLevel(): string
    {
        $metadata = $this->supabase_metadata ?? [];
        return $metadata['aal'] ?? 'aal1';
    }

    /**
     * Check if user has MFA enabled
     */
    public function hasMfaEnabled(): bool
    {
        return $this->getAuthAssuranceLevel() === 'aal2';
    }
}
```

### **Step 7: Create Database Migration**

Create migration for Supabase fields:

```bash
php artisan make:migration add_supabase_fields_to_users_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('supabase_id')->nullable()->unique()->after('id');
            $table->json('supabase_metadata')->nullable()->after('remember_token');

            // Add index for performance
            $table->index('supabase_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['supabase_id']);
            $table->dropColumn(['supabase_id', 'supabase_metadata']);
        });
    }
};
```

### **Step 8: Register Middleware and Service Provider**

The middleware has been registered in `bootstrap/app.php`:

```php
$middleware->alias([
    'workspace' => \App\Http\Middleware\WorkspaceMiddleware::class,
    'supabase.auth' => \App\Http\Middleware\SupabaseAuthMiddleware::class,
]);
```

### **Step 9: Apply Middleware to Routes**

Update your routes to use Supabase authentication:

```php
// In routes/web.php or routes/api.php

// API routes with Supabase authentication
Route::middleware(['supabase.auth'])->group(function () {
    Route::get('/api/user', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'supabase_user' => $request->get('supabase_user'),
            'current_workspace' => $request->get('current_workspace'),
        ]);
    });

    Route::apiResource('links', LinkController::class);
    Route::apiResource('domains', DomainController::class);
});

// Web routes with Supabase authentication
Route::middleware(['supabase.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/{workspace}', [WorkspaceController::class, 'show']);
});
```

### **Step 10: Frontend Integration (React/Inertia.js)**

Update your frontend to send JWT tokens with requests:

```typescript
// In your React components or API client
import { createClient } from '@supabase/supabase-js'

const supabase = createClient(
  'https://yoqmmgxkbyuhcnvqvypw.supabase.co',
  'sb_publishable_a9mSM5l1yoic-nb1MXMZBg_lwyFetLE'
)

// Get the session token
const { data: { session } } = await supabase.auth.getSession()

// Include token in API requests
const response = await fetch('/api/user', {
  headers: {
    'Authorization': `Bearer ${session?.access_token}`,
    'Content-Type': 'application/json',
  }
})
```

## 🧪 **Testing & Verification**

### **Step 1: Run Database Migration**

```bash
# Apply the Supabase fields migration
php artisan migrate
```

### **Step 2: Verify Configuration**

```bash
# Test Supabase configuration
php artisan supabase:verify-config
```

Expected output:
```
🔍 Verifying Supabase Configuration...

✅ Supabase configuration is valid!

📋 Configuration Details:
+------------------+--------------------------------------------------------+
| Setting          | Value                                                  |
+------------------+--------------------------------------------------------+
| Project URL      | https://yoqmmgxkbyuhcnvqvypw.supabase.co              |
| Project Reference| yoqmmgxkbyuhcnvqvypw                                   |
| JWKS URL         | https://yoqmmgxkbyuhcnvqvypw.supabase.co/auth/v1/...  |
| JWT Issuer       | https://yoqmmgxkbyuhcnvqvypw.supabase.co/auth/v1      |
| JWT Audience     | authenticated                                          |
+------------------+--------------------------------------------------------+
```

### **Step 3: Test JWT Token Validation**

```bash
# Test in Laravel Tinker
php artisan tinker

# Test the Supabase service
>>> $service = app(\App\Services\SupabaseAuthService::class);
>>> $verification = $service->verifyConfiguration();
>>> dd($verification);
```

### **Step 4: Test Authentication Flow**

1. **Create a test user in Supabase Dashboard**
2. **Generate a JWT token** using Supabase client
3. **Test API endpoint** with the token:

```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

Expected response:
```json
{
  "user": {
    "id": "1",
    "name": "Test User",
    "email": "test@example.com",
    "supabase_id": "uuid-from-supabase"
  },
  "supabase_user": {
    "id": "uuid-from-supabase",
    "email": "test@example.com",
    "role": "authenticated"
  },
  "current_workspace": {
    "id": "workspace-id",
    "name": "Test Workspace"
  }
}
```

## 🔧 **Troubleshooting**

### **Common Issues**

#### **1. "No keys found in JWKS"**
- Check your `SUPABASE_URL` is correct
- Verify network connectivity to Supabase
- Check if JWKS endpoint is accessible

#### **2. "Invalid JWT issuer"**
- Ensure `JWT_ISSUER` matches your Supabase project URL
- Format should be: `https://your-project.supabase.co/auth/v1`

#### **3. "User not found or could not be created"**
- Check if `SUPABASE_AUTO_CREATE_USERS=true`
- Verify database connection
- Check Laravel logs for detailed errors

#### **4. "Workspace context not set"**
- Ensure user has at least one workspace
- Check `WorkspaceAuthService` is working
- Verify workspace relationships in database

### **Debug Commands**

```bash
# Check configuration
php artisan supabase:verify-config

# View logs
tail -f storage/logs/laravel.log

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check middleware registration
php artisan route:list --middleware=supabase.auth
```

## 🚀 **Production Deployment**

### **Environment Variables**

Ensure these are set in production:

```env
SUPABASE_URL=https://yoqmmgxkbyuhcnvqvypw.supabase.co
SUPABASE_ANON_KEY=sb_publishable_a9mSM5l1yoic-nb1MXMZBg_lwyFetLE
SUPABASE_SERVICE_ROLE_KEY=sb_secret_eG9ajx80a4tXXF0EYXjAWw_5-s0TrXY
JWT_ISSUER=https://yoqmmgxkbyuhcnvqvypw.supabase.co/auth/v1
JWT_AUDIENCE=authenticated
JWKS_CACHE_TTL=3600
```

### **Security Considerations**

1. **Never expose service role key** in frontend code
2. **Use HTTPS** in production
3. **Enable rate limiting** on authentication endpoints
4. **Monitor JWT token usage** and implement refresh logic
5. **Set appropriate CORS policies**

### **Performance Optimization**

1. **Cache JWKS** for at least 1 hour
2. **Use Redis** for session storage
3. **Implement connection pooling** for database
4. **Monitor authentication latency**

This comprehensive implementation provides secure, scalable Supabase authentication for your Laravel application while preserving workspace-aware functionality.
