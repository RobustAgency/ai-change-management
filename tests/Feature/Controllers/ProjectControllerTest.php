<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Plan;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use App\Enums\ProjectStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use App\Jobs\GenerateProjectContentJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private function createUserWithPlan(int $projectLimit = 5): User
    {
        $plan = Plan::factory()->create(['limit' => $projectLimit]);

        return User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);
    }

    public function test_user_can_list_their_projects(): void
    {
        $user = $this->createUserWithPlan();

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

    public function test_user_can_store_project(): void
    {
        Queue::fake();
        $user = $this->createUserWithPlan();

        $payload = [
            'template_id' => 1,
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
        Queue::fake();
        Storage::fake('public');

        $user = $this->createUserWithPlan();

        $payload = [
            'template_id' => 2,
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
        $this->assertNotNull($project->getFirstMediaUrl('client_logos'));
    }

    public function test_user_can_view_single_project(): void
    {
        $user = $this->createUserWithPlan();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Project retrieved successfully',
            'data' => ['id' => $project->id],
        ]);
    }

    public function test_user_can_generate_content(): void
    {
        Queue::fake();
        $user = $this->createUserWithPlan();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Approved,
        ]);

        $response = $this->actingAs($user)->getJson("/api/projects/generate-content/{$project->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Content generation started',
        ]);

        Queue::assertPushed(GenerateProjectContentJob::class, function ($job) use ($project) {
            return $job->getProject()->is($project);
        });
    }

    public function test_user_can_update_project(): void
    {
        $user = $this->createUserWithPlan();
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
            'data' => null,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
        ]);
    }

    public function test_user_can_replace_project_logo_on_update(): void
    {
        Storage::fake('public');

        $user = $this->createUserWithPlan();

        $project = Project::factory()->create(['user_id' => $user->id]);

        $project->addMedia(UploadedFile::fake()->image('old_logo.png'))->toMediaCollection('client_logos');

        $payload = [
            'name' => 'With New Logo',
            'launch_date' => now()->toDateTimeString(),
            'client_logo' => UploadedFile::fake()->image('new_logo.png'),
        ];

        $response = $this->actingAs($user)->postJson("/api/projects/{$project->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'With New Logo',
        ]);

        $freshProject = $project->fresh();

        $this->assertCount(1, $freshProject->getMedia('client_logos'));
        $this->assertStringContainsString(
            'new_logo',
            $freshProject->getFirstMediaUrl('client_logos')
        );
    }

    public function test_delete_project(): void
    {
        $user = $this->createUserWithPlan();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Project deleted successfully',
        ]);
    }

    public function test_user_cannot_generate_content_for_non_completed_project(): void
    {
        Queue::fake();
        $user = $this->createUserWithPlan();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->actingAs($user)->getJson("/api/projects/generate-content/{$project->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Project must be approved before generating AI content.',
        ]);

        Queue::assertNotPushed(GenerateProjectContentJob::class);
    }

    public function test_user_cannot_create_project_without_plan(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER, 'is_active' => true]);

        $payload = [
            'template_id' => 1,
            'name' => 'Test Project',
            'launch_date' => now()->addMonth()->toDateTimeString(),
        ];

        $response = $this->actingAs($user)->postJson('/api/projects', $payload);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'You must have an active subscription plan to create projects.',
        ]);
    }

    public function test_user_cannot_create_project_when_plan_limit_reached(): void
    {
        $user = $this->createUserWithPlan(2); // Plan with limit of 2 projects

        // Create 2 projects to reach the limit
        Project::factory()->count(2)->create(['user_id' => $user->id]);

        $payload = [
            'template_id' => 1,
            'name' => 'Third Project',
            'launch_date' => now()->addMonth()->toDateTimeString(),
        ];

        $response = $this->actingAs($user)->postJson('/api/projects', $payload);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'You have reached your plan limit of 2 projects. Please upgrade your plan to create more projects.',
        ]);
    }

    public function test_user_can_create_project_within_plan_limit(): void
    {
        $user = $this->createUserWithPlan(3); // Plan with limit of 3 projects

        // Create 1 project, still within limit
        Project::factory()->create(['user_id' => $user->id]);

        $payload = [
            'template_id' => 1,
            'name' => 'Second Project',
            'launch_date' => now()->addMonth()->toDateTimeString(),
            'status' => 'draft',
        ];

        $response = $this->actingAs($user)->postJson('/api/projects', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'error' => false,
            'message' => 'Project created successfully',
        ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Second Project',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_update_project_with_ai_content(): void
    {
        $user = $this->createUserWithPlan();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
            'launch_date' => now()->toDateTimeString(),
        ]);

        // Create AI content for the project
        $project->aiContent()->create([
            'slides' => 'Test slides content',
            'emails' => 'Test emails content',
            'faqs' => 'Test FAQs content',
            'video_script' => '{"scenes": [{"title": "Test Scene"}]}',
        ]);

        $payload = [
            'name' => 'Updated Project Name',
            'launch_date' => now()->toDateTimeString(),
            'business_goals' => 'Improve processes',
        ];

        $response = $this->actingAs($user)->postJson("/api/projects/{$project->id}", $payload);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'You cannot edit a project once AI content has been generated.',
        ]);

        // Verify the project name was not updated
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Original Name',
        ]);
    }
}
