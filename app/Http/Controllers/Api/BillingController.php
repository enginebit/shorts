<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\BillingService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Billing API Controller
 *
 * Handles billing, subscription management, and usage tracking
 * following dub-main patterns from billing API endpoints
 */
final class BillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billing,
        private readonly StripeService $stripe
    ) {}

    /**
     * Get billing overview for workspace
     * GET /api/billing/overview
     */
    public function overview(Request $request): JsonResponse
    {
        $user = Auth::user();
        $workspaceId = $request->query('workspaceId');

        // Verify workspace access
        $project = Project::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($workspaceId);

        $usageStats = $this->billing->getUsageStats($project);
        $subscription = $this->stripe->getSubscription($project);
        $paymentMethods = $this->stripe->getPaymentMethods($project);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'plan' => $project->plan,
                'billing_enabled' => $project->billing_enabled,
                'trial_ends_at' => $project->trial_ends_at,
                'payment_failed_at' => $project->payment_failed_at,
            ],
            'usage' => $usageStats,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'canceled_at' => $subscription->canceled_at,
            ] : null,
            'payment_methods' => array_map(function ($pm) {
                return [
                    'id' => $pm->id,
                    'type' => $pm->type,
                    'card' => $pm->card ? [
                        'brand' => $pm->card->brand,
                        'last4' => $pm->card->last4,
                        'exp_month' => $pm->card->exp_month,
                        'exp_year' => $pm->card->exp_year,
                    ] : null,
                ];
            }, $paymentMethods),
        ]);
    }

    /**
     * Get usage statistics
     * GET /api/billing/usage
     */
    public function usage(Request $request): JsonResponse
    {
        $user = Auth::user();
        $workspaceId = $request->query('workspaceId');

        $project = Project::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($workspaceId);

        $usageStats = $this->billing->getUsageStats($project);

        return response()->json($usageStats);
    }

    /**
     * Create subscription
     * POST /api/billing/subscription
     */
    public function createSubscription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspaceId' => 'required|string|exists:projects,id',
            'priceId' => 'required|string',
            'paymentMethodId' => 'sometimes|string',
        ]);

        $user = Auth::user();
        $project = Project::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($validated['workspaceId']);

        try {
            $subscription = $this->stripe->createSubscription(
                $project,
                $validated['priceId'],
                $validated['paymentMethodId'] ?? null
            );

            return response()->json([
                'success' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'client_secret' => $subscription->latest_invoice->payment_intent->client_secret ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create subscription',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update subscription
     * PUT /api/billing/subscription
     */
    public function updateSubscription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspaceId' => 'required|string|exists:projects,id',
            'priceId' => 'required|string',
        ]);

        $user = Auth::user();
        $project = Project::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($validated['workspaceId']);

        try {
            $subscription = $this->stripe->updateSubscription($project, $validated['priceId']);

            if (! $subscription) {
                return response()->json([
                    'error' => 'No active subscription found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update subscription',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel subscription
     * DELETE /api/billing/subscription
     */
    public function cancelSubscription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspaceId' => 'required|string|exists:projects,id',
            'immediately' => 'sometimes|boolean',
        ]);

        $user = Auth::user();
        $project = Project::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($validated['workspaceId']);

        try {
            $subscription = $this->stripe->cancelSubscription(
                $project,
                $validated['immediately'] ?? false
            );

            if (! $subscription) {
                return response()->json([
                    'error' => 'No active subscription found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'cancel_at_period_end' => $subscription->cancel_at_period_end,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel subscription',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create setup intent for payment method
     * POST /api/billing/setup-intent
     */
    public function createSetupIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspaceId' => 'required|string|exists:projects,id',
        ]);

        $user = Auth::user();
        $project = Project::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($validated['workspaceId']);

        try {
            $setupIntent = $this->stripe->createSetupIntent($project);

            return response()->json([
                'success' => true,
                'client_secret' => $setupIntent['client_secret'],
                'setup_intent_id' => $setupIntent['setup_intent_id'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create setup intent',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get billing history
     * GET /api/billing/history
     */
    public function billingHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $workspaceId = $request->query('workspaceId');
        $limit = (int) $request->query('limit', 10);

        $project = Project::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($workspaceId);

        $invoices = $this->stripe->getBillingHistory($project, $limit);

        return response()->json([
            'invoices' => array_map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'status' => $invoice->status,
                    'amount_due' => $invoice->amount_due,
                    'amount_paid' => $invoice->amount_paid,
                    'currency' => $invoice->currency,
                    'created' => $invoice->created,
                    'due_date' => $invoice->due_date,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url,
                    'invoice_pdf' => $invoice->invoice_pdf,
                    'period_start' => $invoice->period_start,
                    'period_end' => $invoice->period_end,
                ];
            }, $invoices),
        ]);
    }

    /**
     * Get available plans
     * GET /api/billing/plans
     */
    public function plans(): JsonResponse
    {
        // This would typically come from Stripe or a configuration file
        $plans = [
            [
                'id' => 'free',
                'name' => 'Free',
                'price' => 0,
                'interval' => 'month',
                'features' => [
                    '25 links',
                    '1K clicks/month',
                    '3 custom domains',
                    'Basic analytics',
                ],
                'limits' => $this->billing->getPlanLimits('free'),
            ],
            [
                'id' => 'starter',
                'name' => 'Starter',
                'price' => 19,
                'interval' => 'month',
                'stripe_price_id' => 'price_starter',
                'features' => [
                    '1K links',
                    '25K clicks/month',
                    '10 custom domains',
                    'Advanced analytics',
                    'Custom QR codes',
                ],
                'limits' => $this->billing->getPlanLimits('starter'),
            ],
            [
                'id' => 'pro',
                'name' => 'Pro',
                'price' => 59,
                'interval' => 'month',
                'stripe_price_id' => 'price_pro',
                'features' => [
                    '5K links',
                    '100K clicks/month',
                    '40 custom domains',
                    'Advanced analytics',
                    'Custom QR codes',
                    'API access',
                    'Team collaboration',
                ],
                'limits' => $this->billing->getPlanLimits('pro'),
            ],
        ];

        return response()->json(['plans' => $plans]);
    }
}
