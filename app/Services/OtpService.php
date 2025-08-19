<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * OTP Service
 *
 * Based on dub-main's OTP system for email verification
 * Handles OTP generation, validation, and email sending
 */
final class OtpService
{
    private const OTP_EXPIRY_MINUTES = 10;
    private const OTP_LENGTH = 6;

    public function __construct(
        private readonly EmailService $emailService
    ) {}

    /**
     * Generate and send OTP for email verification
     */
    public function sendOtp(string $email, ?string $password = null): void
    {
        // Rate limiting
        $key = 'send-otp:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 2)) {
            throw ValidationException::withMessages([
                'email' => ['Too many requests. Please try again later.'],
            ]);
        }

        RateLimiter::hit($key, 60); // 1 minute

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => ['User already exists. Please login instead.'],
            ]);
        }

        // Generate OTP
        $code = $this->generateOtp();

        // Clean up existing tokens for this email
        EmailVerificationToken::where('identifier', $email)->delete();

        // Create new token
        EmailVerificationToken::create([
            'identifier' => $email,
            'token' => $code,
            'expires' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        // Send email
        $this->emailService->sendVerificationEmail($email, $code);
    }

    /**
     * Verify OTP and create user account
     */
    public function verifyOtpAndCreateUser(string $email, string $password, string $code): User
    {
        // Find valid token
        $token = EmailVerificationToken::where('identifier', $email)
            ->where('token', $code)
            ->valid()
            ->first();

        if (!$token) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code.'],
            ]);
        }

        // Check if user already exists (race condition protection)
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => ['User already exists. Please login instead.'],
            ]);
        }

        // Create user
        $user = User::create([
            'name' => explode('@', $email)[0], // Default name from email
            'email' => $email,
            'password_hash' => bcrypt($password),
            'email_verified_at' => now(),
            'subscribed' => true,
            'source' => 'web',
        ]);

        // Clean up the token
        $token->delete();

        return $user;
    }

    /**
     * Generate a random 6-digit OTP
     */
    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Clean up expired tokens (should be run via scheduled task)
     */
    public function cleanupExpiredTokens(): int
    {
        return EmailVerificationToken::cleanupExpired();
    }
}
