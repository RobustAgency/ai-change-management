<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function subscribe(Request $request, Plan $plan)
    {
        // Will be fixed when supabase auth is implemented.
        $user = User::first();

        return $user->newSubscription('default', $plan->stripe_price_id)
            ->checkout([
                'success_url' => url('/api/subscription-success'), // Pass frontend URL for success
                'cancel_url' => url('/api/subscription-cancel'), // Pass frontend URL for cancel
            ]);
    }
}
