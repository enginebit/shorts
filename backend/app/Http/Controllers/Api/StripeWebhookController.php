<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Stripe Webhook Controller
 * 
 * Handles Stripe webhook events for subscription management
 * following dub-main patterns from webhook handling
 */
final class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeService $stripe
    ) {}

    /**
     * Handle Stripe webhook
     * POST /api/webhooks/stripe
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$webhookSecret) {
            Log::error('Stripe webhook secret not configured');
            return response('Webhook secret not configured', 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook parsing failed', [
                'error' => $e->getMessage(),
            ]);
            return response('Invalid payload', 400);
        }

        Log::info('Stripe webhook received', [
            'event_type' => $event->type,
            'event_id' => $event->id,
        ]);

        try {
            $this->handleEvent($event);
        } catch (\Exception $e) {
            Log::error('Stripe webhook handling failed', [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            return response('Webhook handling failed', 500);
        }

        return response('Webhook handled successfully', 200);
    }

    /**
     * Handle specific Stripe events
     */
    private function handleEvent(Event $event): void
    {
        match ($event->type) {
            'customer.subscription.created' => $this->handleSubscriptionCreated($event),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            'invoice.created' => $this->handleInvoiceCreated($event),
            'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($event),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
            'invoice.finalized' => $this->handleInvoiceFinalized($event),
            'payment_method.attached' => $this->handlePaymentMethodAttached($event),
            'payment_method.detached' => $this->handlePaymentMethodDetached($event),
            default => Log::info('Unhandled Stripe webhook event', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]),
        };
    }

    /**
     * Handle subscription created
     */
    private function handleSubscriptionCreated(Event $event): void
    {
        $subscription = $event->data->object;
        $project = $this->findProjectByCustomerId($subscription->customer);

        if (!$project) {
            Log::warning('Project not found for subscription created', [
                'customer_id' => $subscription->customer,
                'subscription_id' => $subscription->id,
            ]);
            return;
        }

        $project->update([
            'stripe_subscription_id' => $subscription->id,
            'billing_enabled' => true,
        ]);

        Log::info('Subscription created for project', [
            'project_id' => $project->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Handle subscription updated
     */
    private function handleSubscriptionUpdated(Event $event): void
    {
        $subscription = $event->data->object;
        $project = $this->findProjectBySubscriptionId($subscription->id);

        if (!$project) {
            Log::warning('Project not found for subscription updated', [
                'subscription_id' => $subscription->id,
            ]);
            return;
        }

        // Update plan if price changed
        if (isset($subscription->items->data[0]->price->id)) {
            $priceId = $subscription->items->data[0]->price->id;
            $plan = $this->getPlanFromPriceId($priceId);
            
            if ($plan !== $project->plan) {
                $project->update(['plan' => $plan]);
                
                Log::info('Plan updated for project', [
                    'project_id' => $project->id,
                    'old_plan' => $project->plan,
                    'new_plan' => $plan,
                ]);
            }
        }

        // Handle cancellation
        if ($subscription->cancel_at_period_end) {
            Log::info('Subscription scheduled for cancellation', [
                'project_id' => $project->id,
                'subscription_id' => $subscription->id,
                'cancel_at' => $subscription->current_period_end,
            ]);
        }
    }

    /**
     * Handle subscription deleted
     */
    private function handleSubscriptionDeleted(Event $event): void
    {
        $subscription = $event->data->object;
        $project = $this->findProjectBySubscriptionId($subscription->id);

        if (!$project) {
            Log::warning('Project not found for subscription deleted', [
                'subscription_id' => $subscription->id,
            ]);
            return;
        }

        $project->update([
            'plan' => 'free',
            'billing_enabled' => false,
            'stripe_subscription_id' => null,
        ]);

        Log::info('Subscription deleted for project', [
            'project_id' => $project->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Handle invoice created
     */
    private function handleInvoiceCreated(Event $event): void
    {
        $invoice = $event->data->object;
        $project = $this->findProjectByCustomerId($invoice->customer);

        if (!$project) {
            Log::warning('Project not found for invoice created', [
                'customer_id' => $invoice->customer,
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        Invoice::updateOrCreate(
            ['stripe_invoice_id' => $invoice->id],
            [
                'project_id' => $project->id,
                'stripe_customer_id' => $invoice->customer,
                'stripe_subscription_id' => $invoice->subscription,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'amount_due' => $invoice->amount_due,
                'amount_paid' => $invoice->amount_paid,
                'amount_remaining' => $invoice->amount_remaining,
                'currency' => $invoice->currency,
                'invoice_date' => date('Y-m-d H:i:s', $invoice->created),
                'due_date' => $invoice->due_date ? date('Y-m-d H:i:s', $invoice->due_date) : null,
                'period_start' => $invoice->period_start ? date('Y-m-d H:i:s', $invoice->period_start) : null,
                'period_end' => $invoice->period_end ? date('Y-m-d H:i:s', $invoice->period_end) : null,
                'hosted_invoice_url' => $invoice->hosted_invoice_url,
                'invoice_pdf' => $invoice->invoice_pdf,
            ]
        );

        Log::info('Invoice created for project', [
            'project_id' => $project->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_due,
        ]);
    }

    /**
     * Handle invoice payment succeeded
     */
    private function handleInvoicePaymentSucceeded(Event $event): void
    {
        $invoice = $event->data->object;
        $project = $this->findProjectByCustomerId($invoice->customer);

        if (!$project) {
            Log::warning('Project not found for invoice payment succeeded', [
                'customer_id' => $invoice->customer,
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        // Update invoice status
        Invoice::where('stripe_invoice_id', $invoice->id)->update([
            'status' => 'paid',
            'amount_paid' => $invoice->amount_paid,
            'paid_at' => now(),
        ]);

        // Handle successful payment
        $this->stripe->handleSuccessfulPayment($project);

        Log::info('Invoice payment succeeded for project', [
            'project_id' => $project->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_paid,
        ]);
    }

    /**
     * Handle invoice payment failed
     */
    private function handleInvoicePaymentFailed(Event $event): void
    {
        $invoice = $event->data->object;
        $project = $this->findProjectByCustomerId($invoice->customer);

        if (!$project) {
            Log::warning('Project not found for invoice payment failed', [
                'customer_id' => $invoice->customer,
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        // Update invoice status
        Invoice::where('stripe_invoice_id', $invoice->id)->update([
            'status' => 'open',
        ]);

        // Handle failed payment
        $this->stripe->handleFailedPayment($project);

        Log::warning('Invoice payment failed for project', [
            'project_id' => $project->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_due,
        ]);
    }

    /**
     * Handle invoice finalized
     */
    private function handleInvoiceFinalized(Event $event): void
    {
        $invoice = $event->data->object;
        
        Invoice::where('stripe_invoice_id', $invoice->id)->update([
            'status' => $invoice->status,
            'number' => $invoice->number,
            'hosted_invoice_url' => $invoice->hosted_invoice_url,
            'invoice_pdf' => $invoice->invoice_pdf,
        ]);

        Log::info('Invoice finalized', [
            'invoice_id' => $invoice->id,
            'number' => $invoice->number,
        ]);
    }

    /**
     * Handle payment method attached
     */
    private function handlePaymentMethodAttached(Event $event): void
    {
        $paymentMethod = $event->data->object;
        
        Log::info('Payment method attached', [
            'payment_method_id' => $paymentMethod->id,
            'customer_id' => $paymentMethod->customer,
        ]);
    }

    /**
     * Handle payment method detached
     */
    private function handlePaymentMethodDetached(Event $event): void
    {
        $paymentMethod = $event->data->object;
        
        Log::info('Payment method detached', [
            'payment_method_id' => $paymentMethod->id,
            'customer_id' => $paymentMethod->customer,
        ]);
    }

    /**
     * Find project by Stripe customer ID
     */
    private function findProjectByCustomerId(string $customerId): ?Project
    {
        return Project::where('stripe_customer_id', $customerId)->first();
    }

    /**
     * Find project by Stripe subscription ID
     */
    private function findProjectBySubscriptionId(string $subscriptionId): ?Project
    {
        return Project::where('stripe_subscription_id', $subscriptionId)->first();
    }

    /**
     * Get plan name from Stripe price ID
     */
    private function getPlanFromPriceId(string $priceId): string
    {
        $planMapping = [
            'price_starter' => 'starter',
            'price_pro' => 'pro',
            'price_business' => 'business',
            'price_enterprise' => 'enterprise',
        ];

        return $planMapping[$priceId] ?? 'free';
    }
}
