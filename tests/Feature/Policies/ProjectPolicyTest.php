<?php

namespace Tests\Feature\Policies;

use Tests\TestCase;
use App\Models\Plan;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use App\Policies\ProjectPolicy;
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

        // Create 2 projects (within limit of 5)
        Project::factory()->count(2)->create(['user_id' => $user->id]);

        $policy = new ProjectPolicy;
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

        // Create 3 projects (reached limit of 3)
        Project::factory()->count(3)->create(['user_id' => $user->id]);

        $policy = new ProjectPolicy;
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

        $policy = new ProjectPolicy;
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

        // No projects created yet
        $this->assertEquals(0, $user->projects()->count());

        $policy = new ProjectPolicy;
        $response = $policy->create($user);

        $this->assertTrue($response->allowed());
    }

    public function test_user_can_update_project_they_own_without_ai_content(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $policy = new ProjectPolicy;
        $response = $policy->update($user, $project);

        $this->assertTrue($response->allowed());
    }

    public function test_user_cannot_update_project_they_do_not_own(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $policy = new ProjectPolicy;
        $response = $policy->update($user, $project);

        $this->assertTrue($response->denied());
        $this->assertEquals('You do not own this project.', $response->message());
    }

    public function test_user_cannot_update_project_with_ai_content(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Create AI content for the project
        $project->aiContent()->create([
            'slides' => 'Test slides content',
            'emails' => 'Test emails content',
            'faqs' => 'Test FAQs content',
            'video_script' => '{"scenes": [{"title": "Test Scene"}]}',
        ]);

        $policy = new ProjectPolicy;
        $response = $policy->update($user, $project);

        $this->assertTrue($response->denied());
        $this->assertEquals('You cannot edit a project once AI content has been generated.', $response->message());
    }
}
