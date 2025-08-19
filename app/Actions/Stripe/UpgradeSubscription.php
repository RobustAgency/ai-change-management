<?php

namespace App\Actions\Stripe;

use App\Models\Plan;
use App\Models\User;

class UpgradeSubscription
{
    public function execute(User $user, Plan $plan): bool
    {
        $subscription = $user->subscription('default');
        $subscription->swap($plan->stripe_price_id);
        $subscription = $subscription->fresh();

        return $subscription->stripe_price === $plan->stripe_price_id;
    }
}
