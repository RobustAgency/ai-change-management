<?php

namespace Tests\Feature\Controllers\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_admin_can_view_user_without_transactions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::USER]);

        $response = $this->actingAs($admin)->getJson("/api/admin/users/{$user->id}");
        dd($response->json());
        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'credit',
                'is_company_detail_complete',
                'is_banking_detail_complete',
                'is_approved',
                'wallets',
                'transactions',
                'created_at',
                'updated_at',
            ]);

        // Verify that transactions array is empty
        $responseData = $response->json();
        $this->assertArrayHasKey('transactions', $responseData);
        $this->assertEmpty($responseData['transactions']);
    }
}
