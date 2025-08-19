<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EmailVerificationToken Model
 *
 * Based on dub-main's emailVerificationToken schema
 * Used for OTP-based email verification during registration
 */
final class EmailVerificationToken extends Model
{
    protected $table = 'otp_verification_tokens';

    protected $fillable = [
        'identifier',
        'token',
        'expires',
    ];

    protected $casts = [
        'expires' => 'datetime',
    ];

    /**
     * Check if the token is valid (not expired)
     */
    public function isValid(): bool
    {
        return $this->expires->isFuture();
    }

    /**
     * Check if the token matches
     */
    public function matches(string $token): bool
    {
        return $this->token === $token;
    }

    /**
     * Scope to get valid tokens only
     */
    public function scopeValid($query)
    {
        return $query->where('expires', '>', now());
    }

    /**
     * Clean up expired tokens
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires', '<=', now())->delete();
    }
}
