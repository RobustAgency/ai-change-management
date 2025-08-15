<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
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
