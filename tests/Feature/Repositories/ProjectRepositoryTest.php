<?php

namespace Tests\Feature\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Enums\ProjectStatus;
use App\Repositories\ProjectRepository;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectRepositoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_it_can_filter_projects_by_term(): void
    {
        $user = User::factory()->create();
        Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'New HR Policy',
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Finance Rollout',
        ]);

        $repository = app(ProjectRepository::class);
        $results = $repository->getFilteredProjects($user, ['term' => 'HR']);

        $this->assertCount(1, $results);
        $this->assertEquals('New HR Policy', $results->first()->name);
    }

    public function test_it_can_filter_projects_by_status(): void
    {
        $user = User::factory()->create();
        Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Draft,
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Completed,
        ]);

        $repository = app(ProjectRepository::class);
        $results = $repository->getFilteredProjects($user, [
            'status' => ProjectStatus::Draft,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals(ProjectStatus::Draft, $results->first()->status);
    }

    public function test_it_can_filter_projects_by_launch_date(): void
    {
        $user = User::factory()->create();
        $today = now()->toDateString();

        Project::factory()->create([
            'user_id' => $user->id,
            'launch_date' => $today,
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'launch_date' => now()->addDay()->toDateString(),
        ]);

        $repository = app(ProjectRepository::class);
        $results = $repository->getFilteredProjects($user, [
            'launch_date' => $today,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($today, $results->first()->launch_date->toDateString());
    }
}
