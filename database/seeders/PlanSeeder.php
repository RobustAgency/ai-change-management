<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Enums\BillingCycle;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'description' => 'Basic plan with essential features',
                'limit' => 1,
                'price' => 29.99,
                'stripe_key' => 'basic',
            ],
            [
                'name' => 'Standard',
                'description' => 'Standard plan with additional features',
                'limit' => 3,
                'price' => 59.99,
                'stripe_key' => 'standard',
            ],
            [
                'name' => 'Premium',
                'description' => 'Premium plan with all features',
                'limit' => 5,
                'price' => 99.99,
                'stripe_key' => 'premium',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name']],
                [
                    'description' => $plan['description'],
                    'limit' => $plan['limit'],
                    'price' => $plan['price'],
                    'billing_cycle' => BillingCycle::Monthly,
                    'currency' => 'usd',
                    'stripe_price_id' => config("cashier.prices.{$plan['stripe_key']}"),
                    'active' => true,
                ]
            );
        }
    }
}
