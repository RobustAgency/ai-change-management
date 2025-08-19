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
        $billingUrl = $user->billingPortalUrl(url('/'));

        return response()->json([
            'error' => false,
            'message' => 'Redirecting to billing portal.',
            'data' => ['redirect_url' => $billingUrl],
        ]);
    }
}
