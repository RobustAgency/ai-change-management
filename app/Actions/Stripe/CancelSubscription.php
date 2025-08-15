<?php

namespace App\Actions\Stripe;

use App\Models\User;
use App\Models\UserSubscriptionHistory;

class CancelSubscription
{
    public function execute(User $user): bool
    {
        $subscriptionName = config('subscription.subscription_name');
        $subscription = $user->subscription($subscriptionName);

        if (! $subscription) {
            return false;
        }

        $subscription->cancel();

        UserSubscriptionHistory::where('user_id', $user->id)
            ->update(['is_active' => false]);

        return true;
    }
}
