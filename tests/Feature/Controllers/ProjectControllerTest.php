<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use App\Enums\ProjectStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_user_can_store_project(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER, 'is_approved' => true]);

        $payload = [
            'name' => 'ERP Rollout',
            'launch_date' => now()->addMonth()->toDateTimeString(),
            'type' => 'new system',
            'sponsor_name' => 'Jane Doe',
            'sponsor_title' => 'CFO',
            'business_goals' => 'Streamline operations',
            'summary' => 'ERP rollout across company',
            'expected_outcomes' => 'Faster reporting and reduced errors',
            'stakeholders' => [
                ['department' => 'Finance', 'role_level' => 'Manager'],
            ],
            'client_organization' => 'Test Org',
            'status' => 'draft',
        ];

        $response = $this->actingAs($user)->postJson('/api/projects', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'error' => false,
            'message' => 'Project created successfully',
        ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'ERP Rollout',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_store_project_with_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => UserRole::USER, 'is_approved' => true]);

        $payload = [
            'name' => 'Brand New Initiative',
            'launch_date' => now()->toDateTimeString(),
            'client_logo' => UploadedFile::fake()->image('logo.png'),
            'status' => ProjectStatus::Completed->value,
        ];

        $response = $this->actingAs($user)->post('/api/projects', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('projects', ['name' => 'Brand New Initiative']);

        $project = Project::first();

        $this->assertNotNull($project);
        $this->assertNotNull($project->getFirstMediaUrl('client_logo'));
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

    public function test_user_can_update_project(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER, 'is_approved' => true]);
        $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Old Name', 'launch_date' => now()->toDateTimeString()]);

        $payload = [
            'name' => 'Updated Project Name',
            'launch_date' => now()->toDateTimeString(),
            'business_goals' => 'Improve processes',
        ];

        $response = $this->actingAs($user)->postJson("/api/projects/{$project->id}", $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Project updated successfully',
            'data' => ['name' => 'Updated Project Name'],
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
        ]);
    }

    public function test_user_can_replace_project_logo_on_update(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => UserRole::USER, 'is_approved' => true]);

        $project = Project::factory()->create(['user_id' => $user->id]);

        $project->addMedia(UploadedFile::fake()->image('old_logo.png'))->toMediaCollection('client_logo');

        $payload = [
            'name' => 'With New Logo',
            'launch_date' => now()->toDateTimeString(),
            'client_logo' => UploadedFile::fake()->image('new_logo.png'),
        ];

        $response = $this->actingAs($user)->postJson("/api/projects/{$project->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'With New Logo']);

        $this->assertStringContainsString('new_logo', $project->fresh()->getFirstMediaUrl('client_logo'));
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
