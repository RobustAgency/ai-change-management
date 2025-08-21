<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    public function addPaymentMethod(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $user->createOrGetStripeCustomer();

        /** @var string $redirectUrl */
        $redirectUrl = url(config('cashier.redirect_url'));
        $billingUrl = $user->billingPortalUrl($redirectUrl);

        return response()->json([
            'error' => false,
            'message' => 'Redirecting to billing portal.',
            'data' => ['redirect_url' => $billingUrl],
        ]);
    }
}
