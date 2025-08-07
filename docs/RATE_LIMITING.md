# Rate Limiting Implementation

This document describes the comprehensive rate limiting system implemented to protect our Laravel + React + Inertia.js URL shortener application from abuse and ensure fair usage.

## Overview

Our rate limiting implementation follows the patterns used by dub-main but adapted for Laravel's built-in rate limiting capabilities. It provides different rate limits for different types of operations and users.

## Rate Limiting Configuration

### Rate Limiters

| Limiter | Authenticated Users | Unauthenticated Users | Purpose |
|---------|-------------------|---------------------|---------|
| `api` | 1000/minute | 60/minute | General API access |
| `auth` | 5/minute + 20/hour | 5/minute + 20/hour | Authentication endpoints |
| `links` | 100/minute | 10/minute | Link creation/management |
| `analytics` | 200/minute | 30/minute | Analytics data access |
| `workspaces` | 50/minute | 10/minute | Workspace operations |
| `redirects` | 1000/minute | 1000/minute | Link redirects (high traffic) |
| `emails` | 5/minute + 50/hour | 5/minute + 50/hour | Email operations |
| `password-reset` | 3/minute + 10/hour | 3/minute + 10/hour | Password reset attempts |

### Key Features

1. **User-Based Limits**: Authenticated users get higher limits based on their user ID
2. **IP-Based Limits**: Unauthenticated users are limited by IP address
3. **Multiple Time Windows**: Some endpoints have both per-minute and per-hour limits
4. **Endpoint-Specific Limits**: Different endpoints have appropriate limits for their use case

## Implementation Details

### RouteServiceProvider

The rate limiting configuration is defined in `app/Providers/RouteServiceProvider.php`:

```php
protected function configureRateLimiting(): void
{
    // General API rate limiting
    RateLimiter::for('api', function (Request $request) {
        if ($request->user()) {
            return Limit::perMinute(1000)->by($request->user()->id);
        }
        return Limit::perMinute(60)->by($request->ip());
    });
    
    // Authentication endpoints - stricter limits
    RateLimiter::for('auth', function (Request $request) {
        return [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(20)->by($request->ip()),
        ];
    });
    
    // ... other rate limiters
}
```

### Middleware

The `RateLimitMiddleware` handles rate limiting enforcement:

- **Dynamic Limits**: Resolves appropriate limits based on the limiter type
- **Custom Messages**: Provides specific error messages for different endpoints
- **JSON/HTML Responses**: Returns appropriate response format based on request type
- **Retry Headers**: Includes retry-after information in responses

### Route Protection

Routes are protected using the `rate.limit` middleware:

```php
// Authentication routes with strict limits
Route::prefix('auth')->middleware('rate.limit:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Link operations with moderate limits
Route::middleware('rate.limit:links')->group(function () {
    Route::apiResource('links', LinksController::class);
});
```

## Error Handling

### JSON Responses

For API requests, rate limit exceeded responses include:

```json
{
    "error": "Rate limit exceeded",
    "message": "Too many authentication attempts. Please try again in 45 seconds.",
    "retry_after": 45
}
```

### HTML Responses

For web requests, users see a custom 429 error page (`resources/views/errors/429.blade.php`) with:

- Clear explanation of the rate limit
- Countdown timer showing when they can retry
- Automatic page refresh after the retry period
- Navigation options to return to the application

## Security Benefits

### Protection Against Attacks

1. **Brute Force Protection**: Authentication endpoints have very strict limits
2. **Spam Prevention**: Link creation and email operations are rate limited
3. **Resource Protection**: Analytics and API endpoints prevent excessive usage
4. **DDoS Mitigation**: High-traffic endpoints like redirects have appropriate limits

### Fair Usage

1. **User Differentiation**: Authenticated users get higher limits
2. **Endpoint Optimization**: Different limits based on resource intensity
3. **Time Window Variety**: Both short-term and long-term limits prevent sustained abuse

## Monitoring and Metrics

### Rate Limit Headers

All responses include standard rate limiting headers:

- `X-RateLimit-Limit`: Maximum number of requests allowed
- `X-RateLimit-Remaining`: Number of requests remaining in the current window
- `X-RateLimit-Reset`: Time when the rate limit window resets

### Testing

Comprehensive tests verify:

- Rate limits are enforced correctly
- Different endpoints have different limits
- Authenticated vs unauthenticated user limits
- Error responses are properly formatted
- Rate limiter independence (different endpoints don't affect each other)

## Configuration

### Environment Variables

No additional environment variables are required. Rate limiting uses Laravel's default cache configuration.

### Customization

Rate limits can be adjusted in `RouteServiceProvider::configureRateLimiting()`:

```php
// Increase link creation limits for authenticated users
RateLimiter::for('links', function (Request $request) {
    if ($request->user()) {
        return Limit::perMinute(200)->by($request->user()->id); // Increased from 100
    }
    return Limit::perMinute(10)->by($request->ip());
});
```

## Comparison with Dub-Main

### Advantages of Our Implementation

1. **Laravel Integration**: Uses Laravel's built-in rate limiting (more mature)
2. **Flexible Configuration**: Easy to adjust limits per endpoint
3. **Better Error Handling**: Custom error pages and messages
4. **Comprehensive Testing**: Full test coverage for rate limiting scenarios

### Dub-Main Patterns Adopted

1. **Endpoint-Specific Limits**: Different limits for different operations
2. **User-Based Scaling**: Higher limits for authenticated users
3. **Multiple Time Windows**: Both per-minute and per-hour limits
4. **High-Traffic Optimization**: Appropriate limits for redirect endpoints

## Best Practices

### For Developers

1. **Test Rate Limits**: Always test new endpoints with rate limiting
2. **Appropriate Limits**: Choose limits based on expected usage patterns
3. **Error Handling**: Ensure your frontend handles 429 responses gracefully
4. **User Communication**: Provide clear feedback when rate limits are hit

### For Operations

1. **Monitor Usage**: Watch for patterns that might indicate abuse
2. **Adjust Limits**: Modify limits based on actual usage patterns
3. **Cache Performance**: Ensure Redis is properly configured for rate limiting
4. **Alert Setup**: Monitor for excessive rate limiting events

## Future Enhancements

1. **Dynamic Limits**: Adjust limits based on user subscription tiers
2. **Geographic Limits**: Different limits for different regions
3. **Behavioral Analysis**: More sophisticated abuse detection
4. **Rate Limit Analytics**: Dashboard for monitoring rate limit usage

This rate limiting implementation provides robust protection against abuse while maintaining excellent user experience for legitimate users.
