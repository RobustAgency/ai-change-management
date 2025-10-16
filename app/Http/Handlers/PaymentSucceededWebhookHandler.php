<?php

namespace App\Http\Handlers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PaymentSucceededWebhookHandler
{
    public function handle(array $payload): void
    {
        Log::info('Stripe invoice.payment_succeeded received.', ['payload' => $payload]);

        $invoice = $payload['data']['object'] ?? null;
        if (! $invoice) {
            Log::warning('Missing invoice data in payment succeeded webhook.');

            return;
        }

        $stripeCustomerId = $invoice['customer'] ?? null;
        $lineItems = $invoice['lines']['data'] ?? [];
        $firstItem = $lineItems[0] ?? [];
        $stripePriceId = $firstItem['pricing']['price_details']['price'] ?? null;

        if (! $stripeCustomerId || ! $stripePriceId) {
            Log::warning('Missing Stripe customer or price ID in webhook.', [
                'customer' => $stripeCustomerId,
                'price_id' => $stripePriceId,
            ]);

            return;
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();
        $plan = Plan::where('stripe_price_id', $stripePriceId)->first();

        if (! $user || ! $plan) {
            Log::warning('No matching user or plan found for Stripe IDs.', [
                'stripe_customer_id' => $stripeCustomerId,
                'stripe_price_id' => $stripePriceId,
            ]);

            return;
        }

        $user->update(['plan_id' => $plan->id]);

        Log::info('User plan updated after successful payment.', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'stripe_price_id' => $stripePriceId,
        ]);
    }
}
