<?php

namespace App\Actions\Stripe;

use App\Models\User;
use App\Models\UserSubscriptionHistory;

class CancelSubscription
{
    public function execute(User $user): array
    {
        $subscriptionName = config('subscription.subscription_name');
        $subscription = $user->subscription($subscriptionName);

        if ($subscription) {
            $subscription->cancel();

            UserSubscriptionHistory::where('user_id', $user->id)
                ->update(['is_active' => false]);

            return [
                'error' => false,
                'message' => 'Subscription canceled successfully.',
                'data' => null,
            ];
        }

        return [
            'error' => true,
            'message' => 'No active subscription found.',
            'data' => null,
        ];
    }
}
