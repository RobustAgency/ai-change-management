<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_list_their_projects(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER, 'is_approved' => true]);

        Project::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/projects');

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Projects retrieved successfully',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => ['id', 'name', 'status', 'launch_date'],
                ],
                'current_page',
                'per_page',
                'total',
            ],
        ]);
    }

    public function test_user_can_filter_projects_by_term(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER, 'is_approved' => true]);

        Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'New HR Policy',
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Finance Rollout',
        ]);

        $response = $this->actingAs($user)->getJson('/api/projects?term=HR');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('New HR Policy', $response->json('data.data.0.name'));
    }

    public function test_user_can_filter_projects_by_status(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER, 'is_approved' => true]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->getJson('/api/projects?status=draft');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('draft', $response->json('data.data.0.status'));
    }

    public function test_user_can_view_single_project(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER, 'is_approved' => true]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Project retrieved successfully',
            'data' => ['id' => $project->id],
        ]);
    }

    public function test_delete_project(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER, 'is_approved' => true]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Project deleted successfully',
        ]);
    }
}
