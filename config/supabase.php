<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Supabase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Supabase integration including authentication,
    | database connections, and API access.
    |
    */

    'url' => env('SUPABASE_URL', 'https://yoqmmgxkbyuhcnvqvypw.supabase.co'),
    'anon_key' => env('SUPABASE_ANON_KEY', 'sb_publishable_a9mSM5l1yoic-nb1MXMZBg_lwyFetLE'),
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY', 'sb_secret_eG9ajx80a4tXXF0EYXjAWw_5-s0TrXY'),
    'project_ref' => env('SUPABASE_PROJECT_REF', 'yoqmmgxkbyuhcnvqvypw'),

    /*
    |--------------------------------------------------------------------------
    | JWT Configuration
    |--------------------------------------------------------------------------
    |
    | JWT validation settings for Supabase authentication tokens.
    | These settings must match your Supabase project configuration.
    |
    */

    'jwt' => [
        'algorithm' => env('JWT_ALGORITHM', 'ES256'),
        'issuer' => env('JWT_ISSUER', 'https://yoqmmgxkbyuhcnvqvypw.supabase.co/auth/v1'),
        'audience' => env('JWT_AUDIENCE', 'authenticated'),
        'key_id' => env('JWT_KEY_ID', 'fffddca8-20fe-4db1-abf5-eb8503df0077'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWKS Configuration
    |--------------------------------------------------------------------------
    |
    | JSON Web Key Set configuration for dynamic key fetching and caching.
    |
    */

    'jwks_cache_ttl' => (int) env('JWKS_CACHE_TTL', 3600), // 1 hour
    'jwks_url' => env('SUPABASE_URL', 'https://yoqmmgxkbyuhcnvqvypw.supabase.co').'/auth/v1/.well-known/jwks.json',

    /*
    |--------------------------------------------------------------------------
    | Authentication Settings
    |--------------------------------------------------------------------------
    |
    | Control how Supabase authentication integrates with Laravel.
    |
    */

    'auth' => [
        'auto_create_users' => env('SUPABASE_AUTO_CREATE_USERS', true),
        'sync_user_metadata' => env('SUPABASE_SYNC_USER_METADATA', true),
        'workspace_aware' => env('SUPABASE_WORKSPACE_AWARE', true),
        'require_email_verification' => env('SUPABASE_REQUIRE_EMAIL_VERIFICATION', false),
        'allowed_roles' => ['authenticated', 'anon'],
        'default_role' => 'authenticated',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for making API calls to Supabase services.
    |
    */

    'api' => [
        'timeout' => (int) env('SUPABASE_API_TIMEOUT', 30),
        'retry_attempts' => (int) env('SUPABASE_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => (int) env('SUPABASE_API_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Supabase PostgreSQL database connection settings.
    | These are used for direct database operations when needed.
    |
    */

    'database' => [
        'host' => env('SUPABASE_DB_HOST'),
        'port' => env('SUPABASE_DB_PORT', 5432),
        'database' => env('SUPABASE_DB_DATABASE', 'postgres'),
        'username' => env('SUPABASE_DB_USERNAME'),
        'password' => env('SUPABASE_DB_PASSWORD'),
        'schema' => env('SUPABASE_DB_SCHEMA', 'public'),
        'sslmode' => env('SUPABASE_DB_SSLMODE', 'require'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Supabase Storage bucket configuration for file uploads.
    |
    */

    'storage' => [
        'default_bucket' => env('SUPABASE_STORAGE_BUCKET', 'avatars'),
        'public_url' => env('SUPABASE_URL', 'https://yoqmmgxkbyuhcnvqvypw.supabase.co').'/storage/v1/object/public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Realtime Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for Supabase Realtime subscriptions.
    |
    */

    'realtime' => [
        'enabled' => env('SUPABASE_REALTIME_ENABLED', false),
        'url' => str_replace('https://', 'wss://', env('SUPABASE_URL', 'https://yoqmmgxkbyuhcnvqvypw.supabase.co')).'/realtime/v1/websocket',
    ],

    /*
    |--------------------------------------------------------------------------
    | Edge Functions Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Supabase Edge Functions.
    |
    */

    'functions' => [
        'url' => env('SUPABASE_URL', 'https://yoqmmgxkbyuhcnvqvypw.supabase.co').'/functions/v1',
        'timeout' => (int) env('SUPABASE_FUNCTIONS_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Control logging behavior for Supabase operations.
    |
    */

    'logging' => [
        'enabled' => env('SUPABASE_LOGGING_ENABLED', true),
        'level' => env('SUPABASE_LOGGING_LEVEL', 'info'),
        'log_requests' => env('SUPABASE_LOG_REQUESTS', false),
        'log_responses' => env('SUPABASE_LOG_RESPONSES', false),
    ],
];
