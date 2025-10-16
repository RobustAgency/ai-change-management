<?php

namespace App\Http\Handlers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class CheckoutSessionCompletedHandler
{
    public function handle(array $payload): void
    {
        $session = $payload['data']['object'] ?? [];
        $customerId = $session['customer'] ?? null;
        $subscriptionId = $session['subscription'] ?? null;

        if (! $customerId || ! $subscriptionId) {
            Log::warning('Missing customer or subscription in checkout.session.completed.');

            return;
        }

        $user = User::where('stripe_id', $customerId)->first();
        if (! $user) {
            Log::warning('User not found for checkout.session.completed.', ['customer' => $customerId]);

            return;
        }

        // Sync subscription from Stripe
        $user->syncStripeSubscription($subscriptionId);

        Log::info('Subscription synced successfully.', [
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
        ]);
    }
}
