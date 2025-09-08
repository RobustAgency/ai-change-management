<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
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
        // private CreateNewSubscription $createNewSubscription,
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
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if (! $subscription || $subscription->ended()) {
            $checkoutDetails = $user->newSubscription('default', $plan->stripe_price_id)
                ->checkout([
                    'success_url' => config('cashier.success_url'),
                    'cancel_url' => config('cashier.cancel_url'),
                ]);

            return response()->json([
                'error' => false,
                'message' => 'Checkout session created successfully.',
                'data' => ['checkout_url' => $checkoutDetails->asStripeCheckoutSession()->url],
            ]);
        }

        // Existing subscription management
        if ($subscription->onGracePeriod() && $plan->isSameAsSubscription($subscription)) {
            $subscriptionRenewed = $this->resumeSubscription->execute($user, $plan);

            if ($subscriptionRenewed) {
                $user->update(['plan_id' => $plan->id]);

                return response()->json([
                    'error' => false,
                    'message' => 'Subscription renewed successfully.',
                    'data' => null,
                ]);
            }
        }

        return response()->json([
            'error' => true,
            'message' => 'You already have an active subscription.',
            'data' => null,
        ], 400);
    }

    public function switch(Plan $plan): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $subscription = $user->subscription('default');

        // Swap plan based on price difference
        if ($subscription->active() && ! $plan->isSameAsSubscription($subscription)) {
            $currentPlan = Plan::currentPlanFor($subscription);
            if ($currentPlan && $plan->isUpgradeTo($currentPlan)) {
                $planUpgraded = $this->upgradeSubscription->execute($user, $plan);

                if ($planUpgraded) {
                    $user->update(['plan_id' => $plan->id]);

                    return response()->json([
                        'error' => false,
                        'message' => 'Subscription upgraded successfully.',
                        'data' => null,
                    ]);
                }
            }

            $planDowngraded = $this->downgradeSubscription->execute($user, $plan);

            if ($planDowngraded) {
                $user->update(['plan_id' => $plan->id]);

                return response()->json([
                    'error' => false,
                    'message' => 'Subscription downgraded successfully.',
                    'data' => null,
                ]);
            }

        }

        return response()->json([
            'error' => false,
            'message' => 'No changes made to the subscription.',
            'data' => null,
        ]);
    }

    public function cancel(CancelSubscription $cancelSubscription)
    {
        /** @var User $user */
        $user = Auth::user();
        $success = $cancelSubscription->execute($user);

        if (! $success) {
            return response()->json([
                'error' => true,
                'message' => 'No active subscription found.',
                'data' => null,
            ], 404);
        }

        $user->update(['plan_id' => null]);

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
        $stripeInvoices = $user->invoices();

        $generateInvoices = [];

        foreach ($stripeInvoices as $stripeInvoice) {
            $invoice = $stripeInvoice->asStripeInvoice();
            $generateInvoices[] = [
                'invoice_number' => $invoice->number,
                'created_at' => Carbon::createFromTimestamp($invoice->created)->format('Y-m-d H:i:s'),
                'amount_paid' => $invoice->amount_paid / 100,
                'status' => $invoice->status,
                'downloadUrl' => $invoice->invoice_pdf,
            ];
        }

        return response()->json([
            'error' => false,
            'message' => 'Invoices retrieved successfully.',
            'data' => $generateInvoices,
        ]);
    }

    public function upcomingInvoice()
    {
        /** @var User $user */
        $user = Auth::user();
        $invoice = $user->upcomingInvoice();

        if (! $invoice) {
            return response()->json([
                'error' => true,
                'message' => 'No upcoming invoice found.',
                'data' => null,
            ]);
        }

        $stripeInvoice = $invoice->asStripeInvoice();
        $upcomingInvoice = [
            'invoice_number' => $stripeInvoice->number ?? null,
            'created_at' => Carbon::createFromTimestamp($stripeInvoice->created)->format('Y-m-d H:i:s'),
            'amount_due' => $stripeInvoice->amount_due / 100,
            'status' => $stripeInvoice->status ?? 'upcoming',
            'download_url' => $invoice->invoice_pdf ?? null,
        ];

        return response()->json([
            'error' => false,
            'message' => 'Upcoming invoice retrieved successfully.',
            'data' => $upcomingInvoice,
        ]);

    }
}
