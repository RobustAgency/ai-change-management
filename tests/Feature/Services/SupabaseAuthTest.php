<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Facades\Supabase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupabaseAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authentication with Supabase facade.
     */
    public function test_supabase_auth_helper(): void
    {
        $user = User::factory()->create([
            'supabase_id' => 'test-supabase-id',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Authenticate using the facade
        Supabase::actingAs($user);

        // Simply verify that the auth guard has the correct user
        $authUser = auth('supabase')->user();
        $this->assertNotNull($authUser);
        $this->assertTrue($user->is($authUser));
    }

    /**
     * Test accessing a protected API endpoint with Supabase auth.
     */
    public function test_accessing_api_with_supabase_auth(): void
    {
        $user = User::factory()->create([
            'supabase_id' => 'test-supabase-id',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Authenticate using the facade
        Supabase::actingAs($user);

        // Access the user info endpoint
        $response = $this->getJson('/api/profile');

        // Assert successful response
        $response->assertStatus(200);

        // The response should contain the user data
        $response->assertJsonFragment([
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }
}
