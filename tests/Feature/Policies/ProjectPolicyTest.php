<?php

namespace Tests\Feature\Policies;

use Tests\TestCase;
use App\Models\Plan;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use App\Enums\ProjectStatus;
use App\Policies\ProjectPolicy;
use App\Enums\ProjectContentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project_when_within_plan_limit(): void
    {
        $plan = Plan::factory()->create(['limit' => 5]);
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        Project::factory()->count(2)->create(['user_id' => $user->id]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->create($user);

        $this->assertTrue($response->allowed());
    }

    public function test_user_cannot_create_project_when_plan_limit_reached(): void
    {
        $plan = Plan::factory()->create(['limit' => 3]);
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        Project::factory()->count(3)->create(['user_id' => $user->id]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->create($user);

        $this->assertTrue($response->denied());
        $this->assertEquals('You have reached your plan limit of 3 projects. Please upgrade your plan to create more projects.', $response->message());
    }

    public function test_user_cannot_create_project_without_plan(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => null,
        ]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->create($user);

        $this->assertTrue($response->denied());
        $this->assertEquals('You must have an active subscription plan to create projects.', $response->message());
    }

    public function test_user_can_create_first_project_with_plan(): void
    {
        $plan = Plan::factory()->create(['limit' => 1]);
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $this->assertEquals(0, $user->projects()->count());

        $policy = app(ProjectPolicy::class);
        $response = $policy->create($user);

        $this->assertTrue($response->allowed());
    }

    public function test_user_can_update_project_they_own_without_ai_content(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->update($user, $project);

        $this->assertTrue($response->allowed());
    }

    public function test_user_cannot_update_project_they_do_not_own(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->update($user, $project);

        $this->assertTrue($response->denied());
        $this->assertEquals('You do not own this project.', $response->message());
    }

    public function test_user_cannot_update_project_with_ai_content(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $project->aiContent()->create([
            'slides' => 'Test slides content',
            'emails' => 'Test emails content',
            'faqs' => 'Test FAQs content',
            'video_script' => '{"scenes": [{"title": "Test Scene"}]}',
        ]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->update($user, $project);

        $this->assertTrue($response->denied());
        $this->assertEquals('You cannot edit a project once AI content has been generated.', $response->message());
    }

    public function test_user_can_generate_content_for_completed_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Completed,
            'content_generation_status' => ProjectContentStatus::Pending,
        ]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->generateContent($user, $project);

        $this->assertTrue($response->allowed());
    }

    public function test_user_cannot_generate_content_for_project_they_do_not_own(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'status' => ProjectStatus::Completed,
            'content_generation_status' => ProjectContentStatus::Pending,
        ]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->generateContent($user, $project);

        $this->assertTrue($response->denied());
        $this->assertEquals('You do not own this project.', $response->message());
    }

    public function test_user_cannot_generate_content_when_ai_content_already_exists(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Completed,
            'content_generation_status' => ProjectContentStatus::Completed,
        ]);

        $project->aiContent()->create([
            'slides' => 'Test slides content',
            'emails' => 'Test emails content',
            'faqs' => 'Test FAQs content',
            'video_script' => '{"scenes": [{"title": "Test Scene"}]}',
        ]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->generateContent($user, $project);

        $this->assertTrue($response->denied());
        $this->assertEquals('AI content has already been generated for this project.', $response->message());
    }

    public function test_user_cannot_generate_content_for_non_completed_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Draft,
            'content_generation_status' => ProjectContentStatus::Pending,
        ]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->generateContent($user, $project);

        $this->assertTrue($response->denied());
        $this->assertEquals('Project must be completed before generating AI content.', $response->message());
    }

    public function test_user_cannot_generate_content_when_generation_in_progress(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Completed,
            'content_generation_status' => ProjectContentStatus::InProgress,
        ]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->generateContent($user, $project);

        $this->assertTrue($response->denied());
        $this->assertEquals('Content generation is already in progress.', $response->message());
    }

    public function test_user_cannot_generate_content_when_generation_completed(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Completed,
            'content_generation_status' => ProjectContentStatus::Completed,
        ]);

        $policy = app(ProjectPolicy::class);
        $response = $policy->generateContent($user, $project);

        $this->assertTrue($response->denied());
        $this->assertEquals('Content generation has already been completed.', $response->message());
    }
}
