<?php

namespace Tests\Feature\Controllers;

use Mockery;
use Tests\TestCase;
use App\Models\Plan;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use App\Enums\BillingCycle;
use Tests\Fakes\FakeSupabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/auth/v1/admin/users' => function ($request) {
                $requestData = $request->data();

                return Http::response(FakeSupabase::getUserCreationResponse([
                    'email' => $requestData['email'],
                    'name' => $requestData['user_metadata']['name'] ?? 'Test User',
                    'email_verified' => $requestData['email_confirm'] ?? true,
                ]), 200);
            },
        ]);
    }

    public function test_get_active_plans(): void
    {
        $user = User::factory()->create([
            'id' => 1,
            'role' => UserRole::USER,
            'is_active' => true,
        ]);

        Plan::factory()->create([
            'id' => 1,
            'name' => 'Basic Plan',
            'stripe_price_id' => 'price_123',
            'active' => true,
        ]);
        $response = $this->actingAs($user)->getJson('/api/plans');

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Plans retrieved successfully.',
        ]);
    }

    public function test_get_user_invoices(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
        ]);

        // Fake Stripe invoice object
        $stripeInvoice = (object) [
            'number' => 'INV-001',
            'created' => now()->timestamp,
            'amount_paid' => 1000,
            'status' => 'paid',
            'invoice_pdf' => 'http://fake-invoice.pdf',
        ];

        // Mock Cashier Invoice wrapper
        $cashierInvoiceMock = Mockery::mock(\Laravel\Cashier\Invoice::class);
        $cashierInvoiceMock->shouldReceive('asStripeInvoice')->andReturn($stripeInvoice);

        $userMock = Mockery::mock($user)->makePartial();
        $userMock->shouldReceive('invoices')->once()->andReturn([$cashierInvoiceMock]);

        $this->app->instance(User::class, $userMock);

        $response = $this->actingAs($userMock)->getJson('/api/plans/invoices');

        $response->assertOk()->assertJson([
            'error' => false,
            'message' => 'Invoices retrieved successfully.',
        ]);
    }

    public function test_get_user_upcoming_invoice(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
        ]);

        // Fake Stripe upcoming invoice object
        $stripeUpcomingInvoice = (object) [
            'number' => 'INV-UPCOMING-001',
            'created' => now()->timestamp,
            'amount_due' => 1500,
            'status' => 'open',
            'invoice_pdf' => null,
        ];

        // Mock Cashier Invoice wrapper
        $upcomingInvoiceMock = Mockery::mock(\Laravel\Cashier\Invoice::class);
        $upcomingInvoiceMock->shouldReceive('asStripeInvoice')->andReturn($stripeUpcomingInvoice);

        $userMock = Mockery::mock($user)->makePartial();
        $userMock->shouldReceive('upcomingInvoice')->once()->andReturn($upcomingInvoiceMock);

        $this->app->instance(User::class, $userMock);

        $response = $this->actingAs($userMock)->getJson('/api/plans/upcoming-invoice');

        $response->assertOk()->assertJson([
            'error' => false,
            'message' => 'Upcoming invoice retrieved successfully.',
            'data' => [
                'invoice_number' => 'INV-UPCOMING-001',
                'amount_due' => 15,
                'status' => 'open',
            ],
        ]);
    }

    public function test_current_subscription_returns_complete_data(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Standard',
            'price' => 20.00,
            'limit' => 10,
            'currency' => 'USD',
            'billing_cycle' => BillingCycle::Monthly,
            'stripe_price_id' => 'price_test_123',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        // Create some projects for the user
        Project::factory()->count(3)->create(['user_id' => $user->id]);

        // Mock the subscription
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_123',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $response = $this->actingAs($user, 'supabase')
            ->getJson('/api/plans/current-subscription');

        $response->assertOk();
        $response->assertJsonStructure([
            'error',
            'message',
            'data' => [
                'plan_name',
                'price',
                'currency',
                'billing_cycle',
                'status',
                'next_billing_date',
                'project_usage' => [
                    'current',
                    'limit',
                ],
                'usage_resets_at',
            ],
        ]);

        $data = $response->json('data');

        $this->assertEquals('Standard', $data['plan_name']);
        $this->assertEquals(20.00, $data['price']);
        $this->assertEquals('USD', $data['currency']);
        $this->assertEquals('monthly', $data['billing_cycle']);
        $this->assertEquals('Active', $data['status']);
        $this->assertEquals(3, $data['project_usage']['current']);
        $this->assertEquals(10, $data['project_usage']['limit']);
    }

    public function test_current_subscription_returns_404_when_no_subscription(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => null,
        ]);

        $response = $this->actingAs($user, 'supabase')
            ->getJson('/api/plans/current-subscription');

        $response->assertNotFound();
        $response->assertJson([
            'error' => true,
            'message' => 'No active subscription found.',
        ]);
    }

    public function test_current_subscription_shows_cancelled_status_on_grace_period(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Premium',
            'price' => 50.00,
            'limit' => 25,
            'currency' => 'USD',
            'billing_cycle' => BillingCycle::Monthly,
            'stripe_price_id' => 'price_test_456',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        // Create subscription on grace period (cancelled but still active)
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_456',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => now()->addDays(7), // Grace period
        ]);

        $response = $this->actingAs($user, 'supabase')
            ->getJson('/api/plans/current-subscription');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('Cancelled', $data['status']);
        $this->assertNotNull($data['next_billing_date']);
    }

    public function test_current_subscription_calculates_project_usage_correctly(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Basic',
            'price' => 10.00,
            'limit' => 5,
            'currency' => 'USD',
            'billing_cycle' => BillingCycle::Monthly,
            'stripe_price_id' => 'price_test_789',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        // Create 5 projects (at limit)
        Project::factory()->count(5)->create(['user_id' => $user->id]);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_789',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $response = $this->actingAs($user, 'supabase')
            ->getJson('/api/plans/current-subscription');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals(5, $data['project_usage']['current']);
        $this->assertEquals(5, $data['project_usage']['limit']);
    }

    public function test_current_subscription_returns_yearly_billing_cycle(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Enterprise',
            'price' => 200.00,
            'limit' => 100,
            'currency' => 'USD',
            'billing_cycle' => BillingCycle::Yearly,
            'stripe_price_id' => 'price_test_yearly',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_yearly',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $response = $this->actingAs($user, 'supabase')
            ->getJson('/api/plans/current-subscription');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('yearly', $data['billing_cycle']);
        $this->assertEquals(200.00, $data['price']);
    }
}
