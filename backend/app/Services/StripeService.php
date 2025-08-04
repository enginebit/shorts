<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\PaymentMethod;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\SubscriptionItem;
use Stripe\WebhookEndpoint;
use Exception;

/**
 * Stripe Service
 * 
 * Handles Stripe integration for subscription management, billing,
 * and payment processing following dub-main patterns
 */
final class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret_key'));
        Stripe::setAppInfo('Shorts', '1.0.0', 'https://shorts.enginebit.com');
    }

    /**
     * Create or retrieve Stripe customer
     * Following dub-main patterns from workspace billing
     */
    public function createOrGetCustomer(User $user, Project $project): Customer
    {
        if ($project->stripe_customer_id) {
            try {
                return Customer::retrieve($project->stripe_customer_id);
            } catch (Exception $e) {
                Log::warning('Failed to retrieve existing Stripe customer', [
                    'project_id' => $project->id,
                    'stripe_customer_id' => $project->stripe_customer_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'project_slug' => $project->slug,
                ],
            ]);

            // Update project with Stripe customer ID
            $project->update(['stripe_customer_id' => $customer->id]);

            Log::info('Created Stripe customer', [
                'project_id' => $project->id,
                'customer_id' => $customer->id,
            ]);

            return $customer;
        } catch (Exception $e) {
            Log::error('Failed to create Stripe customer', [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create subscription for project
     * Following dub-main subscription patterns
     */
    public function createSubscription(Project $project, string $priceId, ?string $paymentMethodId = null): Subscription
    {
        $customer = $this->createOrGetCustomer($project->users()->first(), $project);

        try {
            $subscriptionData = [
                'customer' => $customer->id,
                'items' => [
                    ['price' => $priceId],
                ],
                'billing_cycle_anchor' => $project->billing_cycle_start ?? 1,
                'metadata' => [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ];

            if ($paymentMethodId) {
                $subscriptionData['default_payment_method'] = $paymentMethodId;
            }

            $subscription = Subscription::create($subscriptionData);

            // Update project with subscription details
            $project->update([
                'stripe_subscription_id' => $subscription->id,
                'plan' => $this->getPlanFromPriceId($priceId),
                'billing_cycle_start' => $subscription->billing_cycle_anchor ?? 1,
            ]);

            Log::info('Created Stripe subscription', [
                'project_id' => $project->id,
                'subscription_id' => $subscription->id,
                'plan' => $project->plan,
            ]);

            return $subscription;
        } catch (Exception $e) {
            Log::error('Failed to create Stripe subscription', [
                'project_id' => $project->id,
                'price_id' => $priceId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancel subscription
     * Following dub-main patterns from cancel-subscription.ts
     */
    public function cancelSubscription(Project $project, bool $immediately = false): ?Subscription
    {
        if (!$project->stripe_subscription_id) {
            return null;
        }

        try {
            if ($immediately) {
                $subscription = Subscription::retrieve($project->stripe_subscription_id);
                $subscription->cancel();
            } else {
                $subscription = Subscription::update($project->stripe_subscription_id, [
                    'cancel_at_period_end' => true,
                    'cancellation_details' => [
                        'comment' => 'Customer cancelled their subscription.',
                    ],
                ]);
            }

            Log::info('Cancelled Stripe subscription', [
                'project_id' => $project->id,
                'subscription_id' => $project->stripe_subscription_id,
                'immediately' => $immediately,
            ]);

            return $subscription;
        } catch (Exception $e) {
            Log::error('Failed to cancel Stripe subscription', [
                'project_id' => $project->id,
                'subscription_id' => $project->stripe_subscription_id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update subscription plan
     */
    public function updateSubscription(Project $project, string $newPriceId): ?Subscription
    {
        if (!$project->stripe_subscription_id) {
            return null;
        }

        try {
            $subscription = Subscription::retrieve($project->stripe_subscription_id);
            
            $subscription = Subscription::update($project->stripe_subscription_id, [
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'price' => $newPriceId,
                    ],
                ],
                'proration_behavior' => 'create_prorations',
            ]);

            // Update project plan
            $project->update([
                'plan' => $this->getPlanFromPriceId($newPriceId),
            ]);

            Log::info('Updated Stripe subscription', [
                'project_id' => $project->id,
                'subscription_id' => $project->stripe_subscription_id,
                'new_plan' => $project->plan,
            ]);

            return $subscription;
        } catch (Exception $e) {
            Log::error('Failed to update Stripe subscription', [
                'project_id' => $project->id,
                'subscription_id' => $project->stripe_subscription_id,
                'new_price_id' => $newPriceId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get subscription details
     */
    public function getSubscription(Project $project): ?Subscription
    {
        if (!$project->stripe_subscription_id) {
            return null;
        }

        try {
            return Subscription::retrieve($project->stripe_subscription_id);
        } catch (Exception $e) {
            Log::error('Failed to retrieve Stripe subscription', [
                'project_id' => $project->id,
                'subscription_id' => $project->stripe_subscription_id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get customer payment methods
     */
    public function getPaymentMethods(Project $project): array
    {
        if (!$project->stripe_customer_id) {
            return [];
        }

        try {
            $paymentMethods = PaymentMethod::all([
                'customer' => $project->stripe_customer_id,
                'type' => 'card',
            ]);

            return $paymentMethods->data;
        } catch (Exception $e) {
            Log::error('Failed to retrieve payment methods', [
                'project_id' => $project->id,
                'customer_id' => $project->stripe_customer_id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get billing history (invoices)
     */
    public function getBillingHistory(Project $project, int $limit = 10): array
    {
        if (!$project->stripe_customer_id) {
            return [];
        }

        try {
            $invoices = Invoice::all([
                'customer' => $project->stripe_customer_id,
                'limit' => $limit,
            ]);

            return $invoices->data;
        } catch (Exception $e) {
            Log::error('Failed to retrieve billing history', [
                'project_id' => $project->id,
                'customer_id' => $project->stripe_customer_id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Create setup intent for payment method
     */
    public function createSetupIntent(Project $project): array
    {
        $customer = $this->createOrGetCustomer($project->users()->first(), $project);

        try {
            $setupIntent = \Stripe\SetupIntent::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'usage' => 'off_session',
            ]);

            return [
                'client_secret' => $setupIntent->client_secret,
                'setup_intent_id' => $setupIntent->id,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create setup intent', [
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get plan name from Stripe price ID
     */
    private function getPlanFromPriceId(string $priceId): string
    {
        // This would typically map Stripe price IDs to plan names
        // Following dub-main plan structure
        $planMapping = [
            'price_starter' => 'starter',
            'price_pro' => 'pro',
            'price_business' => 'business',
            'price_enterprise' => 'enterprise',
        ];

        return $planMapping[$priceId] ?? 'free';
    }

    /**
     * Handle failed payment
     */
    public function handleFailedPayment(Project $project): void
    {
        $project->update([
            'payment_failed_at' => now(),
        ]);

        Log::warning('Payment failed for project', [
            'project_id' => $project->id,
            'subscription_id' => $project->stripe_subscription_id,
        ]);

        // TODO: Send notification email
        // TODO: Implement grace period logic
    }

    /**
     * Handle successful payment
     */
    public function handleSuccessfulPayment(Project $project): void
    {
        $project->update([
            'payment_failed_at' => null,
        ]);

        Log::info('Payment successful for project', [
            'project_id' => $project->id,
            'subscription_id' => $project->stripe_subscription_id,
        ]);
    }
}
