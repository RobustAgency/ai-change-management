<?php

namespace App\Actions\Stripe;

use App\Models\Plan;
use App\Models\User;

class CreateNewSubscription
{
    public function execute(User $user, Plan $plan): bool
    {
        $user->newSubscription('default', $plan->stripe_price_id)->create();

        return $user->subscribed('default') && $user->subscription('default')->active();
    }
}
