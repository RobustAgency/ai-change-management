<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Actions\ManageUserSubscription;
use App\Actions\Stripe\CancelSubscription;

class BillingController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::where('active', true)->get();

        return response()->json([
            'error' => false,
            'message' => 'Plans retrieved successfully.',
            'data' => $plans,
        ]);
    }

    public function subscribe(Plan $plan, ManageUserSubscription $manageUserSubscription)
    {
        // Temporary: replace with authenticated user once Supabase auth is implemented.
        /** @var User $user */
        $user = User::first();

        $user->createOrGetStripeCustomer();

        if (! $user->hasPaymentMethod()) {
            return $user->redirectToBillingPortal(url("/plans/subscribe/{$plan->id}"));
        }

        return $manageUserSubscription->execute($user, $plan);
    }

    public function cancel(CancelSubscription $cancelSubscription)
    {
        // Will be fixed when supabase auth is implemented.
        $user = User::first();

        return response()->json(
            $cancelSubscription->execute($user)
        );
    }
}
