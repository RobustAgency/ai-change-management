<?php

namespace Database\Seeders;

use App\Enums\BillingCycle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                'limit' => 5,
                'price' => 10.00,
                'billing_cycle' => BillingCycle::Monthly,
                'currency' => 'usd',
                'stripe_price_id' => config('cashier.prices.basic'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),

            ],
            [
                'name' => 'Standard',
                'description' => 'Standard plan with additional features',
                'limit' => 10,
                'price' => 20.00,
                'billing_cycle' => BillingCycle::Monthly,
                'currency' => 'usd',
                'stripe_price_id' => config('cashier.prices.standard'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium',
                'description' => 'Premium plan with all features',
                'limit' => 15,
                'price' => 30.00,
                'billing_cycle' => BillingCycle::Monthly,
                'currency' => 'usd',
                'stripe_price_id' => config('cashier.prices.premium'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('plans')->insert($plans);
    }
}
