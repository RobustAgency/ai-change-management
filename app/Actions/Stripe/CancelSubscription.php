<?php

namespace App\Actions\Stripe;

use App\Models\User;

class CancelSubscription
{
    public function execute(User $user): bool
    {
        $subscription = $user->subscription('default');

        if (! $subscription || ! $subscription->canceled()) {
            return false;
        }

        $subscription->cancel();
        $subscription = $subscription->fresh();

        return $subscription->onGracePeriod();
    }
}
