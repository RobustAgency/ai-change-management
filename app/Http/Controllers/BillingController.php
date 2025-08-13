<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscriptionHistory;
use Stripe\Checkout\Session as StripeSession;

class BillingController extends Controller
{
    public function subscribe(Plan $plan)
    {
        $user = User::first();

        Stripe::setApiKey(config('cashier.secret'));

        $session = StripeSession::create([
            'customer' => $user->stripe_id,
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]],
            'success_url' => url('/subscription-success'),
            'cancel_url' => url('/subscription-cancel'),
        ]);

        return response()->json([
            'url' => $session->url,
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
