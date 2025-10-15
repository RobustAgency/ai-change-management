<?php

namespace Tests\Unit\Repositories;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Repositories\DashboardRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DashboardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DashboardRepository;
    }

    public function test_get_total_projects_returns_correct_count(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(3)->create(['user_id' => $user->id]);

        $count = $this->repository->getTotalProjects($user);

        $this->assertEquals(3, $count);
    }

    public function test_get_projects_this_month_returns_correct_count(): void
    {
        $user = User::factory()->create();

        // Create 2 projects this month
        Project::factory()->count(2)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->startOfMonth()->addDays(5),
        ]);

        // Create 1 project last month
        Project::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subMonth()->addDays(5),
        ]);

        $count = $this->repository->getProjectsThisMonth($user);

        $this->assertEquals(2, $count);
    }

    public function test_get_projects_with_content_returns_correct_count(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->create(['user_id' => $user->id]);

        // Add AI content to 2 projects
        foreach ($projects->take(2) as $project) {
            $project->aiContent()->create([
                'slides' => 'Test slides',
                'emails' => 'Test emails',
                'faqs' => 'Test FAQs',
                'video_script' => '{"scenes": []}',
            ]);
        }

        $count = $this->repository->getProjectsWithContent($user);

        $this->assertEquals(2, $count);
    }

    public function test_get_projects_pending_generation_returns_correct_count(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(4)->create(['user_id' => $user->id]);

        // Add AI content to 1 project
        $projects->first()->aiContent()->create([
            'slides' => 'Test slides',
            'emails' => 'Test emails',
            'faqs' => 'Test FAQs',
            'video_script' => '{"scenes": []}',
        ]);

        $count = $this->repository->getProjectsPendingGeneration($user);

        $this->assertEquals(3, $count); // 4 total - 1 with content = 3 pending
    }

    public function test_get_dashboard_stats_returns_complete_array(): void
    {
        $user = User::factory()->create();

        // Create 3 projects from last month
        $projects = Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subMonth()->addDays(5),
        ]);

        // Create 2 projects this month
        Project::factory()->count(2)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->startOfMonth()->addDays(5),
        ]);

        // Add AI content to 2 projects
        foreach ($projects->take(2) as $project) {
            $project->aiContent()->create([
                'slides' => 'Test slides',
                'emails' => 'Test emails',
                'faqs' => 'Test FAQs',
                'video_script' => '{"scenes": []}',
            ]);
        }

        $stats = $this->repository->getDashboardStats($user);

        $this->assertEquals([
            'total_projects' => 5,
            'this_month' => 2,
            'content_generated' => 2,
            'pending_generation' => 3,
        ], $stats);
    }
}
