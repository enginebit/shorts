<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * OTP Controller
 *
 * Based on dub-main's OTP system
 * Handles OTP generation, sending, and verification for email verification
 */
final class OtpController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService
    ) {}

    /**
     * Send OTP for email verification
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        try {
            $this->otpService->sendOtp($request->email, $request->password);

            return response()->json([
                'message' => 'Verification code sent successfully.',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify OTP and create user account
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'code' => 'required|string|size:6',
        ]);

        try {
            $user = $this->otpService->verifyOtpAndCreateUser(
                $request->email,
                $request->password,
                $request->code
            );

            // Log the user in
            Auth::login($user);

            return response()->json([
                'message' => 'Account created successfully.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'redirect_url' => '/onboarding/welcome',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Verification failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
