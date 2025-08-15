<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = User::find(1);
        $hasPaymentMethod = $user->hasPaymentMethod();

        $data = [
            'user' => $user,
            'has_payment_method' => $hasPaymentMethod,
        ];

        return response()->json([
            'error' => false,
            'message' => 'Profile retrieved successfully.',
            'data' => $data,
        ]);
    }
}
