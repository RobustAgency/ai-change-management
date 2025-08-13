<?php

namespace App\Actions;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use App\Actions\Stripe\ResumeSubscription;
use App\Actions\Stripe\UpgradeSubscription;
use App\Actions\Stripe\CreateNewSubscription;
use App\Actions\Stripe\DowngradeSubscription;

class ManageUserSubscription
{
    public function __construct(
        private CreateNewSubscription $createNewSubscription,
        private ResumeSubscription $resumeSubscription,
        private UpgradeSubscription $upgradeSubscription,
        private DowngradeSubscription $downgradeSubscription,
    ) {}

    public function execute(User $user, Plan $plan): RedirectResponse
    {
        $subscriptionName = config('subscription.subscription_name');
        $redirectUrl = config('subscription.redirect_url');

        $subscription = $user->subscription($subscriptionName);
        // New subscription creation
        if (! $subscription) {
            return $this->createNewSubscription->execute($user, $plan);
        }

        // Existing subscription management
        if ($subscription->ended() || $subscription->onGracePeriod()) {
            return $this->resumeSubscription->execute($user, $plan);
        }

        // Swap plan based on price difference
        if ($subscription->stripe_price !== $plan->stripe_price_id) {
            $currentPlan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();

            if ($currentPlan && $plan->price > $currentPlan->price) {
                return $this->upgradeSubscription->execute($user, $plan);
            }

            return $this->downgradeSubscription->execute($user, $plan);
        }

        return redirect()->to($redirectUrl)->with(['success' => 'Already on the selected plan']);

    }
}
