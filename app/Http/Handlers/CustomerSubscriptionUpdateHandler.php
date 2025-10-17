<?php

namespace App\Http\Handlers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class CustomerSubscriptionUpdateHandler
{
    public function handle(array $payload): void
    {
        Log::info('Handling customer.subscription.updated event', ['payload' => $payload]);

        $subscription = $payload['data']['object'] ?? [];
        $previous = $payload['data']['previous_attributes'] ?? [];

        $customerId = $subscription['customer'] ?? null;
        $subscriptionId = $subscription['id'] ?? null;

        if (! $customerId || ! $subscriptionId) {
            Log::warning('Missing customer or subscription in customer.subscription.updated.');

            return;
        }

        $user = User::where('stripe_id', $customerId)->first();
        if (! $user) {
            Log::warning('User not found for customer.subscription.updated.', ['customer' => $customerId]);

            return;
        }

        $oldPlan = $previous['plan']['id'] ?? null;
        $newPlan = $subscription['plan']['id'] ?? null;

        if ($oldPlan && $newPlan && $oldPlan !== $newPlan) {
            Log::info('Plan changed detected', [
                'user_id' => $user->id,
                'old_plan' => $oldPlan,
                'new_plan' => $newPlan,
            ]);
        }

        // Sync subscription from Stripe
        $user->syncStripeSubscription($subscriptionId);

        Log::info('Subscription synced successfully after plan update.', [
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
        ]);
    }
}
