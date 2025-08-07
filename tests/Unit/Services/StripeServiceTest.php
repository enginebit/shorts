<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Customer;
use Stripe\Subscription;
use Tests\TestCase;

final class StripeServiceTest extends TestCase
{
    use RefreshDatabase;

    private StripeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret_key' => 'sk_test_fake_key',
            'services.stripe.publishable_key' => 'pk_test_fake_key',
            'services.stripe.webhook_secret' => 'whsec_fake_secret',
        ]);

        $this->service = new StripeService;
    }

    public function test_create_customer_success(): void
    {
        // Mock Stripe Customer
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test123';

        // Create test data
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $project = Project::factory()->create([
            'name' => 'Test Project',
            'slug' => 'test-project',
        ]);

        $project->users()->attach($user);

        // Mock Stripe Customer::create
        Customer::shouldReceive('create')
            ->once()
            ->with([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'project_slug' => $project->slug,
                ],
            ])
            ->andReturn($mockCustomer);

        $customer = $this->service->createOrGetCustomer($user, $project);

        $this->assertEquals('cus_test123', $customer->id);

        // Verify project was updated
        $project->refresh();
        $this->assertEquals('cus_test123', $project->stripe_customer_id);
    }

    public function test_retrieve_existing_customer(): void
    {
        // Create test data with existing customer ID
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'stripe_customer_id' => 'cus_existing123',
        ]);
        $project->users()->attach($user);

        // Mock Stripe Customer::retrieve
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_existing123';

        Customer::shouldReceive('retrieve')
            ->once()
            ->with('cus_existing123')
            ->andReturn($mockCustomer);

        $customer = $this->service->createOrGetCustomer($user, $project);

        $this->assertEquals('cus_existing123', $customer->id);
    }

    public function test_create_subscription_success(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user);

        // Mock customer creation
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test123';

        Customer::shouldReceive('create')
            ->once()
            ->andReturn($mockCustomer);

        // Mock subscription creation
        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->id = 'sub_test123';
        $mockSubscription->billing_cycle_anchor = 1;

        Subscription::shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['customer'] === 'cus_test123' &&
                       $data['items'][0]['price'] === 'price_starter' &&
                       isset($data['metadata']['project_id']);
            }))
            ->andReturn($mockSubscription);

        $subscription = $this->service->createSubscription($project, 'price_starter');

        $this->assertEquals('sub_test123', $subscription->id);

        // Verify project was updated
        $project->refresh();
        $this->assertEquals('sub_test123', $project->stripe_subscription_id);
        $this->assertEquals('starter', $project->plan);
    }

    public function test_cancel_subscription_at_period_end(): void
    {
        $project = Project::factory()->create([
            'stripe_subscription_id' => 'sub_test123',
        ]);

        // Mock subscription update
        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->id = 'sub_test123';
        $mockSubscription->cancel_at_period_end = true;

        Subscription::shouldReceive('update')
            ->once()
            ->with('sub_test123', [
                'cancel_at_period_end' => true,
                'cancellation_details' => [
                    'comment' => 'Customer cancelled their subscription.',
                ],
            ])
            ->andReturn($mockSubscription);

        $subscription = $this->service->cancelSubscription($project, false);

        $this->assertNotNull($subscription);
        $this->assertEquals('sub_test123', $subscription->id);
        $this->assertTrue($subscription->cancel_at_period_end);
    }

    public function test_cancel_subscription_immediately(): void
    {
        $project = Project::factory()->create([
            'stripe_subscription_id' => 'sub_test123',
        ]);

        // Mock subscription retrieval and cancellation
        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('cancel')->once();

        Subscription::shouldReceive('retrieve')
            ->once()
            ->with('sub_test123')
            ->andReturn($mockSubscription);

        $subscription = $this->service->cancelSubscription($project, true);

        $this->assertNotNull($subscription);
    }

    public function test_update_subscription_success(): void
    {
        $project = Project::factory()->create([
            'stripe_subscription_id' => 'sub_test123',
            'plan' => 'starter',
        ]);

        // Mock subscription retrieval
        $mockSubscriptionItem = (object) ['id' => 'si_test123'];
        $mockItems = (object) ['data' => [$mockSubscriptionItem]];

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->items = $mockItems;

        Subscription::shouldReceive('retrieve')
            ->once()
            ->with('sub_test123')
            ->andReturn($mockSubscription);

        // Mock subscription update
        $updatedSubscription = Mockery::mock(Subscription::class);
        $updatedSubscription->id = 'sub_test123';

        Subscription::shouldReceive('update')
            ->once()
            ->with('sub_test123', [
                'items' => [
                    [
                        'id' => 'si_test123',
                        'price' => 'price_pro',
                    ],
                ],
                'proration_behavior' => 'create_prorations',
            ])
            ->andReturn($updatedSubscription);

        $subscription = $this->service->updateSubscription($project, 'price_pro');

        $this->assertNotNull($subscription);
        $this->assertEquals('sub_test123', $subscription->id);

        // Verify project plan was updated
        $project->refresh();
        $this->assertEquals('pro', $project->plan);
    }

    public function test_cancel_subscription_without_subscription_id(): void
    {
        $project = Project::factory()->create([
            'stripe_subscription_id' => null,
        ]);

        $result = $this->service->cancelSubscription($project);

        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
