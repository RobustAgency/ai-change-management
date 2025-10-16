<?php

namespace Tests\Feature\Repositories\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Repositories\Admin\DashboardRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_total_users_returns_correct_count(): void
    {
        User::factory()->count(7)->create();

        $repository = app(DashboardRepository::class);
        $count = $repository->getTotalUsers();

        $this->assertEquals(7, $count);
    }

    public function test_get_active_users_returns_correct_count(): void
    {
        User::factory()->count(4)->create(['is_active' => true]);
        User::factory()->count(2)->create(['is_active' => false]);

        $repository = app(DashboardRepository::class);
        $count = $repository->getActiveUsers();

        $this->assertEquals(4, $count);
    }

    public function test_get_inactive_users_returns_correct_count(): void
    {
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->count(5)->create(['is_active' => false]);

        $repository = app(DashboardRepository::class);
        $count = $repository->getInactiveUsers();

        $this->assertEquals(5, $count);
    }

    public function test_get_total_projects_returns_correct_count(): void
    {
        Project::factory()->count(12)->create();

        $repository = app(DashboardRepository::class);
        $count = $repository->getTotalProjects();

        $this->assertEquals(12, $count);
    }

    public function test_get_dashboard_stats_returns_complete_array(): void
    {
        User::factory()->count(6)->create(['is_active' => true]);
        User::factory()->count(4)->create(['is_active' => false]);
        Project::factory()->count(15)->create();

        $repository = app(DashboardRepository::class);
        $stats = $repository->getDashboardStats();

        $this->assertArrayHasKey('total_users', $stats);
        $this->assertArrayHasKey('active_users', $stats);
        $this->assertArrayHasKey('inactive_users', $stats);
        $this->assertArrayHasKey('total_projects', $stats);

        $this->assertEquals($repository->getTotalUsers(), $stats['total_users']);
        $this->assertEquals($repository->getActiveUsers(), $stats['active_users']);
        $this->assertEquals($repository->getInactiveUsers(), $stats['inactive_users']);
        $this->assertEquals($repository->getTotalProjects(), $stats['total_projects']);
    }

    public function test_get_dashboard_stats_with_no_data(): void
    {
        $repository = app(DashboardRepository::class);
        $stats = $repository->getDashboardStats();

        $this->assertEquals([
            'total_users' => 0,
            'active_users' => 0,
            'inactive_users' => 0,
            'total_projects' => 0,
        ], $stats);
    }
}
