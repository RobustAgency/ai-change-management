<?php

namespace Tests\Fakes;

use Illuminate\Support\Str;

class FakeSupabase
{
    /**
     * Get a fake user creation response
     */
    public static function getUserCreationResponse(array $overrides = []): array
    {
        $userId = $overrides['id'] ?? Str::uuid()->toString();
        $email = $overrides['email'] ?? \fake()->email();
        $name = $overrides['name'] ?? \fake()->name();
        $emailVerified = $overrides['email_verified'] ?? true;
        $now = now()->format('Y-m-d\TH:i:s.u\Z');

        return array_merge([
            'id' => $userId,
            'aud' => 'authenticated',
            'role' => 'authenticated',
            'email' => $email,
            'email_confirmed_at' => $emailVerified ? $now : null,
            'phone' => '',
            'app_metadata' => [
                'provider' => 'email',
                'providers' => ['email'],
            ],
            'user_metadata' => [
                'email_verified' => $emailVerified,
                'name' => $name,
            ],
            'identities' => [
                [
                    'identity_id' => Str::uuid()->toString(),
                    'id' => $userId,
                    'user_id' => $userId,
                    'identity_data' => [
                        'email' => $email,
                        'email_verified' => false,
                        'phone_verified' => false,
                        'sub' => $userId,
                    ],
                    'provider' => 'email',
                    'last_sign_in_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'email' => $email,
                ],
            ],
            'created_at' => $now,
            'updated_at' => $now,
            'is_anonymous' => false,
        ], $overrides);
    }

    /**
     * Get a fake user data response
     */
    public static function getUserData(array $overrides = []): array
    {
        $userId = $overrides['id'] ?? Str::uuid()->toString();
        $email = $overrides['email'] ?? \fake()->email();
        $name = $overrides['name'] ?? \fake()->name();
        $now = now()->format('Y-m-d\TH:i:s.u\Z');

        return array_merge([
            'id' => $userId,
            'aud' => 'authenticated',
            'role' => 'authenticated',
            'email' => $email,
            'email_confirmed_at' => $now,
            'phone' => '',
            'app_metadata' => [
                'provider' => 'email',
                'providers' => ['email'],
            ],
            'user_metadata' => [
                'name' => $name,
            ],
            'created_at' => $now,
            'updated_at' => $now,
            'is_anonymous' => false,
        ], $overrides);
    }

    /**
     * Get a fake token validation response
     */
    public static function getTokenValidationResponse(array $overrides = []): array
    {
        return array_merge([
            'supabase_id' => Str::uuid()->toString(),
            'name' => \fake()->name(),
            'email' => \fake()->email(),
        ], $overrides);
    }

    /**
     * Get a fake token refresh response
     */
    public static function getTokenRefreshResponse(): array
    {
        return [
            'access_token' => 'new-access-token-'.Str::random(10),
            'refresh_token' => 'new-refresh-token-'.Str::random(10),
            'expires_in' => 3600,
        ];
    }
}
