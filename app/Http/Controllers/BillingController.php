<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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

    public function addPaymentMethod(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->createOrGetStripeCustomer();
        $billingUrl = $user->billingPortalUrl(url('/')); // Redirects it to the frontend url after adding payment method

        return response()->json([
            'error' => false,
            'message' => 'Redirecting to billing portal.',
            'redirect_url' => $billingUrl,
        ]);
    }

    public function subscribe(Plan $plan, ManageUserSubscription $manageUserSubscription)
    {
        /** @var User $user */
        $user = Auth::user();

        $user->createOrGetStripeCustomer();

        if (! $user->hasPaymentMethod()) {
            $billingUrl = $user->billingPortalUrl(url("/plans/subscribe/{$plan->id}"));

            return response()->json([
                'error' => true,
                'message' => 'You must add a payment method to subscribe.',
                'redirect_url' => $billingUrl,
            ]);
        }

        return $manageUserSubscription->execute($user, $plan);
    }

    public function cancel(CancelSubscription $cancelSubscription)
    {
        $user = Auth::user();
        $success = $cancelSubscription->execute($user);

        if (! $success) {
            return response()->json([
                'error' => true,
                'message' => 'No active subscription found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'error' => false,
            'message' => 'Subscription cancelled successfully.',
            'data' => null,
        ]);
    }
}
