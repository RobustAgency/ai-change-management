<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Laravel\Cashier\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'limit',
        'price',
        'stripe_price_id',
        'billing_cycle',
        'currency',
        'active',
    ];

    protected $casts = [
        'billing_cycle' => BillingCycle::class,
    ];

    public function isSameAsSubscription(?Subscription $subscription): bool
    {
        return $subscription?->stripe_price === $this->stripe_price_id;
    }

    public static function currentPlanFor(?Subscription $subscription): ?self
    {
        if (! $subscription?->stripe_price) {
            return null;
        }

        return self::where('stripe_price_id', $subscription->stripe_price)->first();
    }

    public function isUpgradeTo(Plan $otherPlan): bool
    {
        return $this->price > $otherPlan->price;
    }
}
