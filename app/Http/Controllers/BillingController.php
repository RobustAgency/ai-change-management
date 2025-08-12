<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscriptionHistory;

class BillingController extends Controller
{
    public function subscribe(Plan $plan)
    {
        // Will be fixed when supabase auth is implemented.
        $user = User::first();

        return $user->newSubscription('default', $plan->stripe_price_id)
            ->checkout([
                'success_url' => url('/api/subscription-success'), // Pass frontend URL for success
                'cancel_url' => url('/api/subscription-cancel'), // Pass frontend URL for cancel
            ]);
    }

    public function cancel()
    {
        // Will be fixed when supabase auth is implemented.
        $user = User::first();

        $subscription = $user->subscription('default');

        if ($subscription) {
            $subscription->cancel();

            UserSubscriptionHistory::where('user_id', $user->id)->update([
                'status' => false,
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Subscription canceled successfully.',
                'data' => null,
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'No active subscription found.',
            'data' => null,
        ]);
    }
}
