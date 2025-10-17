<?php

namespace Tests\Unit\Actions\Subscription;

use Tests\TestCase;
use App\Models\Plan;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use App\Enums\BillingCycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Subscription\GetCurrentSubscriptionDetails;

class GetCurrentSubscriptionDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_complete_subscription_details(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Premium',
            'price' => 50.00,
            'limit' => 20,
            'currency' => 'USD',
            'billing_cycle' => BillingCycle::Monthly,
            'stripe_price_id' => 'price_test_premium',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        Project::factory()->count(5)->create(['user_id' => $user->id]);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_premium',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $action = app(GetCurrentSubscriptionDetails::class);
        $result = $action->execute($user);

        $this->assertIsArray($result);
        $this->assertEquals('Premium', $result['plan_name']);
        $this->assertEquals(50.00, $result['price']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals('monthly', $result['billing_cycle']);
        $this->assertEquals('Active', $result['status']);
        $this->assertArrayHasKey('next_billing_date', $result);
        $this->assertEquals(5, $result['project_usage']['current']);
        $this->assertEquals(20, $result['project_usage']['limit']);
        $this->assertArrayHasKey('usage_resets_at', $result);
    }

    public function test_execute_throws_exception_when_no_subscription(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No active subscription found.');

        $action = app(GetCurrentSubscriptionDetails::class);
        $action->execute($user);
    }

    public function test_execute_returns_cancelled_status_on_grace_period(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Basic',
            'price' => 10.00,
            'limit' => 5,
            'currency' => 'USD',
            'billing_cycle' => BillingCycle::Monthly,
            'stripe_price_id' => 'price_test_basic',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_basic',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => now()->addDays(5),
        ]);

        $action = app(GetCurrentSubscriptionDetails::class);
        $result = $action->execute($user);

        $this->assertEquals('Cancelled', $result['status']);
        $this->assertNotNull($result['next_billing_date']);
    }

    public function test_execute_returns_past_due_status(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Standard',
            'price' => 20.00,
            'limit' => 10,
            'currency' => 'USD',
            'billing_cycle' => BillingCycle::Monthly,
            'stripe_price_id' => 'price_test_standard',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_standard',
            'stripe_status' => 'past_due',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $action = app(GetCurrentSubscriptionDetails::class);
        $result = $action->execute($user);

        $this->assertEquals('Past Due', $result['status']);
    }

    public function test_execute_returns_incomplete_status(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Enterprise',
            'price' => 100.00,
            'limit' => 50,
            'currency' => 'USD',
            'billing_cycle' => BillingCycle::Yearly,
            'stripe_price_id' => 'price_test_enterprise',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_enterprise',
            'stripe_status' => 'incomplete',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $action = app(GetCurrentSubscriptionDetails::class);
        $result = $action->execute($user);

        $this->assertEquals('Incomplete', $result['status']);
    }

    public function test_execute_calculates_correct_project_usage(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Starter',
            'price' => 5.00,
            'limit' => 3,
            'currency' => 'USD',
            'billing_cycle' => BillingCycle::Monthly,
            'stripe_price_id' => 'price_test_starter',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        Project::factory()->count(2)->create(['user_id' => $user->id]);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_starter',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $action = app(GetCurrentSubscriptionDetails::class);
        $result = $action->execute($user);

        $this->assertEquals(2, $result['project_usage']['current']);
        $this->assertEquals(3, $result['project_usage']['limit']);
    }
}
