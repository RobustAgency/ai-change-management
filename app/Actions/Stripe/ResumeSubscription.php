<?php

namespace App\Actions\Stripe;

use App\Models\Plan;
use App\Models\User;

class ResumeSubscription
{
    public function execute(User $user, Plan $plan): bool
    {
        $subscription = $user->subscription('default');
        $subscription->resume();

        return ! $subscription->onGracePeriod() && ! $subscription->ended();
    }
}
