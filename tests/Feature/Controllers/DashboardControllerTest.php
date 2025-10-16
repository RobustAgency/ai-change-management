<?php

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Plan;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_dashboard_stats(): void
    {
        $plan = Plan::factory()->create(['limit' => 10]);
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $projects = Project::factory()->count(5)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subMonth()->addDays(5),
        ]);

        Project::factory()->count(2)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->startOfMonth()->addDays(5),
        ]);

        foreach ($projects->take(3) as $project) {
            $project->aiContent()->create([
                'slides' => 'Test slides',
                'emails' => 'Test emails',
                'faqs' => 'Test FAQs',
                'video_script' => '{"scenes": []}',
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => [
                'total_projects' => 7,
                'this_month' => 2,
                'content_generated' => 3,
                'pending_generation' => 4,
            ],
        ]);
    }

    public function test_user_dashboard_stats_with_no_projects(): void
    {
        $plan = Plan::factory()->create(['limit' => 5]);
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => [
                'total_projects' => 0,
                'this_month' => 0,
                'content_generated' => 0,
                'pending_generation' => 0,
            ],
        ]);
    }

    public function test_user_dashboard_stats_with_all_content_generated(): void
    {
        $plan = Plan::factory()->create(['limit' => 5]);
        $user = User::factory()->create([
            'role' => UserRole::USER,
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $projects = Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subMonth()->addDays(10),
        ]);

        foreach ($projects as $project) {
            $project->aiContent()->create([
                'slides' => 'Test slides',
                'emails' => 'Test emails',
                'faqs' => 'Test FAQs',
                'video_script' => '{"scenes": []}',
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertStatus(200);
        $response->assertJson([
            'error' => false,
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => [
                'total_projects' => 3,
                'this_month' => 0,
                'content_generated' => 3,
                'pending_generation' => 0,
            ],
        ]);
    }
}
