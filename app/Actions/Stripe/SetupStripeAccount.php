<?php

namespace App\Actions\Stripe;

use Stripe\Product;
use Stripe\StripeClient;

class SetupStripeAccount
{
    protected StripeClient $stripeClient;

    public function __construct()
    {
        $this->stripeClient = new StripeClient(config('cashier.secret'));
    }

    public function execute(string $productName, array $plans): array
    {
        $product = $this->createProduct($productName);
        \info('The product creation', ['product' => $product]);
        $prices = $this->createRecurringPrices($product->id, $plans);

        return [
            'product' => $product,
            'prices' => $prices,
        ];
    }

    protected function createProduct(string $name): Product
    {
        return $this->stripeClient->products->create([
            'name' => $name,
        ]);
    }

    protected function createRecurringPrices(string $productId, array $plans): array
    {
        $prices = [];

        foreach ($plans as $plan) {
            $prices[$plan['name']] = $this->stripeClient->prices->create([
                'unit_amount' => $plan['amount'],
                'currency' => 'usd',
                'recurring' => ['interval' => 'month'],
                'product' => $productId,
                'nickname' => $plan['name'],
            ]);
        }

        return $prices;
    }
}
