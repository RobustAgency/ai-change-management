<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Clients\SupabaseClient;
use Illuminate\Http\JsonResponse;

class SupabaseController extends Controller
{
    public function login(Request $request, SupabaseClient $supabase): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $response = $supabase->login($credentials['email'], $credentials['password']);

        return response()->json([
            'message' => 'User logged in successfully',
            'data' => ['access_token' => $response['access_token'], 'expires_at' => $response['expires_at']],
        ]);
    }
}
