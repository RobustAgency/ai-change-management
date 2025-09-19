<?php

namespace App\Http\Handlers;

use App\Models\Plan;
use App\Models\User;

class PaymentSucceededWebhookHandler
{
    public function handle(array $payload): void
    {
        \info('Payment succeeded webhook received.', ['payload' => $payload]);
        $priceId = $payload['data']['object']['lines']['data'][0]['price']['id'];

        $plan = Plan::where('stripe_price_id', $priceId)->first();
        $user = User::where('stripe_id', $payload['data']['object']['customer'])->first();

        $user?->update(['plan_id' => $plan->id]);
    }
}
