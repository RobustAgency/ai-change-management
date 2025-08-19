<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Actions\Stripe\CancelSubscription;
use App\Actions\Stripe\ResumeSubscription;
use App\Actions\Stripe\UpgradeSubscription;
use App\Actions\Stripe\CreateNewSubscription;
use App\Actions\Stripe\DowngradeSubscription;

class BillingController extends Controller
{
    public function __construct(
        private CreateNewSubscription $createNewSubscription,
        private DowngradeSubscription $downgradeSubscription,
        private ResumeSubscription $resumeSubscription,
        private UpgradeSubscription $upgradeSubscription
    ) {}

    public function index(): JsonResponse
    {
        $plans = Plan::where('active', true)->get();

        return response()->json([
            'error' => false,
            'message' => 'Plans retrieved successfully.',
            'data' => $plans,
        ]);
    }

    public function subscribe(Plan $plan)
    {
        /** @var User $user */
        $user = User::find(2);

        $user->createOrGetStripeCustomer();

        if (! $user->hasPaymentMethod()) {
            $billingUrl = $user->billingPortalUrl(url("/plans/subscribe/{$plan->id}"));

            return response()->json([
                'error' => true,
                'message' => 'You must add a payment method to subscribe.',
                'data' => ['redirect_url' => $billingUrl],
            ]);
        }
        $subscription = $user->subscription('default');

        // New subscription creation
        if (! $subscription || $subscription->ended()) {
            $subscriptionCreated = $this->createNewSubscription->execute($user, $plan);

            if ($subscriptionCreated) {
                return response()->json([
                    'error' => false,
                    'message' => 'Subscription created successfully.',
                    'data' => null,
                ]);
            }
        }

        // Existing subscription management
        if ($subscription->onGracePeriod()) {
            $subscriptionRenewed = $this->resumeSubscription->execute($user, $plan);

            if ($subscriptionRenewed) {
                return response()->json([
                    'error' => false,
                    'message' => 'Subscription renewed successfully.',
                    'data' => null,
                ]);
            }
        }

        // Swap plan based on price difference
        if ($subscription->active() && $subscription->stripe_price !== $plan->stripe_price_id) {
            $currentPlan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();

            if ($currentPlan && $plan->price > $currentPlan->price) {
                $planUpgraded = $this->upgradeSubscription->execute($user, $plan);

                if ($planUpgraded) {
                    return response()->json([
                        'error' => false,
                        'message' => 'Subscription upgraded successfully.',
                        'data' => null,
                    ]);
                }
            }

            $planDowngraded = $this->downgradeSubscription->execute($user, $plan);

            if ($planDowngraded) {
                return response()->json([
                    'error' => false,
                    'message' => 'Subscription downgraded successfully.',
                    'data' => null,
                ]);
            }

        }

        return response()->json([
            'error' => true,
            'message' => 'No changes made to the subscription.',
            'data' => null,
        ]);
    }

    public function cancel(CancelSubscription $cancelSubscription)
    {
        $user = User::find(2);
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

    public function invoices()
    {
        /** @var User $user */
        $user = Auth::user();
        $invoices = $user->invoices();

        return response()->json([
            'error' => false,
            'message' => 'Invoices retrieved successfully.',
            'data' => $invoices,
        ]);
    }

    public function upcomingInvoice()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $invoice = $user->upcomingInvoice();

        return response()->json([
            'error' => false,
            'data' => $invoice->toArray(),
        ]);

    }
}
