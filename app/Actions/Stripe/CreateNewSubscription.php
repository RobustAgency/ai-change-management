<?php

namespace App\Actions\Stripe;

use App\Models\Plan;
use App\Models\User;
use App\Enums\PlanStatus;
use Illuminate\Http\RedirectResponse;
use App\Models\UserSubscriptionHistory;

class CreateNewSubscription
{
    public function execute(User $user, Plan $plan): RedirectResponse
    {
        $subscriptionName = config('subscription.subscription_name');
        $redirectUrl = config('subscription.redirect_url');

        $user->newSubscription($subscriptionName, $plan->stripe_price_id)->create();

        UserSubscriptionHistory::where('user_id', $user->id)->update(['is_active' => false]);

        UserSubscriptionHistory::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'is_active' => PlanStatus::Active->isActive(),
        ]);

        return redirect()->to($redirectUrl)->with(['success' => 'Subscription created successfully']);
    }
}
