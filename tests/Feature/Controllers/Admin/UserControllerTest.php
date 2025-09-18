<?php

namespace Tests\Feature\Controllers\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use Tests\Fakes\FakeSupabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Supabase client for all tests
        Http::fake([
            // Mock the Supabase auth endpoint for user creation
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

    public function test_admin_can_view_all_users_with_pagination(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        User::factory()->count(5)->create(['role' => UserRole::USER]);
        $response = $this->actingAs($admin)->getJson('/api/admin/users');

        $response->assertOk();

        $responseData = $response->json();
        $this->assertFalse($responseData['error']);
        $this->assertEquals('Users retrieved successfully', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function test_admin_can_view_user(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::USER]);

        $response = $this->actingAs($admin)->getJson("/api/admin/users/{$user->id}");
        $response->assertOk();
        $response->assertJsonStructure([
            'error',
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'is_approved',
                'created_at',
                'updated_at',
            ],
        ]);

        $responseData = $response->json();
        $this->assertFalse($responseData['error']);
        $this->assertEquals('User retrieved successfully', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function test_admin_can_search_users_by_name(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        User::factory()->create([
            'role' => UserRole::USER,
            'name' => 'John Doe',
        ]);

        User::factory()->create([
            'role' => UserRole::USER,
            'name' => 'Jane Smith',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/users/search?term=John');

        $response->assertOk();

        $responseData = $response->json();
        $this->assertFalse($responseData['error']);
        $this->assertEquals('Users retrieved successfully', $responseData['message']);
        $this->assertEquals('John Doe', $responseData['data'][0]['name']);
    }

    public function test_admin_can_search_users_by_email(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        User::factory()->create([
            'role' => UserRole::USER,
            'email' => 'john.doe@example.com',
        ]);

        User::factory()->create([
            'role' => UserRole::USER,
            'email' => 'jane.smith@example.com',
        ]);
        $response = $this->actingAs($admin)->getJson('/api/admin/users/search?term=john.doe');

        $response->assertOk();
        $responseData = $response->json();
        $this->assertFalse($responseData['error']);
        $this->assertEquals('john.doe@example.com', $responseData['data'][0]['email']);
    }

    public function test_admin_can_approve_user(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_approved' => false,
        ]);
        $this->assertFalse($user->is_approved);

        $response = $this->actingAs($admin)->postJson("/api/admin/users/{$user->id}/approve");

        $response->assertOk();
        $response->assertJsonStructure([
            'error',
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'is_approved',
                'created_at',
                'updated_at',
            ],
        ]);

        $responseData = $response->json();
        $this->assertFalse($responseData['error']);
        $this->assertEquals('User approved successfully', $responseData['message']);
        $this->assertTrue($responseData['data']['is_approved']);

        $user->refresh();
        $this->assertTrue($user->is_approved);
    }

    public function test_admin_can_revoke_user_approval(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_approved' => true,
        ]);
        $this->assertTrue($user->is_approved);

        $response = $this->actingAs($admin)->postJson("/api/admin/users/{$user->id}/revoke-approval");

        $response->assertOk();
        $response->assertJsonStructure([
            'error',
            'message',
            'data',
        ]);

        $responseData = $response->json();
        $this->assertFalse($responseData['error']);
        $this->assertEquals('User approval revoked successfully', $responseData['message']);
        $this->assertDatabaseMissing('users', ['id' => $user->id, 'is_approved' => true]);
    }
}
