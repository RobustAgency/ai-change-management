<?php

namespace App\Http\Controllers;

use App\Clients\SupabaseClient;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\LoginRequest;

class SupabaseController extends Controller
{
    public function login(LoginRequest $request, SupabaseClient $supabase): JsonResponse
    {
        $credentials = $request->validated();

        $response = $supabase->login($credentials['email'], $credentials['password']);

        return response()->json([
            'error' => false,
            'message' => 'User logged in successfully',
            'data' => ['access_token' => $response['access_token'], 'expires_at' => $response['expires_at']],
        ]);
    }
}
