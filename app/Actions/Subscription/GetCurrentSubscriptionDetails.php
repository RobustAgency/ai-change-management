<?php

namespace App\Actions\Subscription;

use Carbon\Carbon;
use App\Models\Plan;
use App\Models\User;
use Laravel\Cashier\Subscription;

class GetCurrentSubscriptionDetails
{
    public function execute(User $user): array
    {
        $subscription = $user->subscription('default');
        if (! $subscription || $subscription->ended()) {
            throw new \Exception('No active subscription found.');
        }

        $plan = Plan::currentPlanFor($subscription);

        if (! $plan) {
            throw new \Exception('Plan not found.');
        }

        return [
            'plan_name' => $plan->name,
            'price' => $plan->price,
            'currency' => $plan->currency ?? 'USD',
            'billing_cycle' => $plan->billing_cycle->value,
            'status' => $this->determineSubscriptionStatus($subscription),
            'next_billing_date' => $this->getNextBillingDate($subscription, $plan),
            'project_usage' => $this->getProjectUsage($user, $plan),
            'usage_resets_at' => $this->getNextBillingDate($subscription, $plan),
        ];
    }

    private function determineSubscriptionStatus(Subscription $subscription): string
    {
        if ($subscription->onGracePeriod()) {
            return 'Cancelled';
        }

        if ($subscription->stripe_status === 'past_due') {
            return 'Past Due';
        }

        if ($subscription->stripe_status === 'incomplete') {
            return 'Incomplete';
        }

        return 'Active';
    }

    private function getNextBillingDate(Subscription $subscription, Plan $plan): ?string
    {
        $nextBillingDate = null;

        if ($subscription->onGracePeriod()) {
            $nextBillingDate = $subscription->ends_at;
        } elseif ($subscription->active()) {
            $nextBillingDate = $this->fetchNextBillingDateFromStripe($subscription, $plan);
        }

        return $nextBillingDate ? $nextBillingDate->format('F j, Y') : null;
    }

    private function fetchNextBillingDateFromStripe(Subscription $subscription, Plan $plan): Carbon
    {
        try {
            $stripeSubscription = $subscription->asStripeSubscription();

            return Carbon::createFromTimestamp($stripeSubscription->current_period_end);
        } catch (\Exception $e) {
            // In test environment or when Stripe is unavailable, estimate next billing date
            return $subscription->created_at->add(
                $plan->billing_cycle->value === 'monthly' ? '1 month' : '1 year'
            );
        }
    }

    private function getProjectUsage(User $user, Plan $plan): array
    {
        return [
            'current' => $user->projects()->count(),
            'limit' => $plan->limit,
        ];
    }
}
