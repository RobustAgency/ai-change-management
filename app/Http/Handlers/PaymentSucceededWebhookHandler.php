<?php

namespace App\Http\Handlers;

use App\Models\Plan;
use App\Models\UserSubscriptionHistory;

class PaymentSucceededWebhookHandler
{
    public function handle(array $payload): void
    {
        \info('Payment succeeded webhook received.', ['payload' => $payload]);
        $priceId = $payload['data']['object']['lines']['data'][0]['price']['id'];

        $plan = Plan::where('stripe_price_id', $priceId)->first();

        UserSubscriptionHistory::create([
            'user_id' => $payload['data']['object']['customer'],
            'plan_id' => $plan->id,
            'is_active' => true,
        ]);
    }
}
