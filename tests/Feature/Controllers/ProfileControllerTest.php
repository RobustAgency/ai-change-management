<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use Tests\Fakes\FakeSupabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/auth/v1/admin/users' => function ($request) {
                $requestData = $request->data();

                return Http::response(FakeSupabase::getUserCreationResponse([
                    'email' => $requestData['email'],
                    'name' => $requestData['user_metadata']['name'] ?? 'Test User',
                    'email_verified' => $requestData['email_confirm'] ?? true,
                ]), 200);
            },
        ]);
    }

    public function test_user_can_view_profile(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'id' => 1,
            'role' => UserRole::USER,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertOk();
        $response->assertJsonStructure([
            'error',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'is_approved',
                    'role',
                    'created_at',
                    'updated_at',
                ],
                'has_payment_method',
            ],
        ]);

        $responseData = $response->json();
        $this->assertFalse($responseData['error']);
        $this->assertEquals('Profile retrieved successfully.', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('user', $responseData['data']);
        $this->assertArrayHasKey('has_payment_method', $responseData['data']);
        $this->assertEquals($user->id, $responseData['data']['user']['id']);
        $this->assertEquals($user->email, $responseData['data']['user']['email']);
        $this->assertEquals($user->name, $responseData['data']['user']['name']);
    }

    public function test_non_approved_user_cannot_access_profile(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'id' => 1,
            'role' => UserRole::USER,
            'is_approved' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/api/profile');
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Your account is not approved yet. Please contact support.',
        ]);
    }
}
