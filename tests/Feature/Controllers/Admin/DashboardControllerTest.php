<?php

namespace Tests\Feature\Controllers\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_dashboard_stats(): void
    {
        Event::fake();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        User::factory()->count(5)->create(['is_active' => true]);
        User::factory()->count(3)->create(['is_active' => false]);
        Project::factory()->count(10)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Dashboard stats retrieved successfully',
        ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(9, $data['total_users']);
        $this->assertGreaterThanOrEqual(6, $data['active_users']);
        $this->assertEquals(3, $data['inactive_users']);
        $this->assertEquals(10, $data['total_projects']);
    }

    public function test_admin_dashboard_stats_with_no_data(): void
    {
        Event::fake();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Dashboard stats retrieved successfully',
            'data' => [
                'total_users' => 0,
                'active_users' => 0,
                'inactive_users' => 0,
                'total_projects' => 0,
            ],
        ]);
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }
}
