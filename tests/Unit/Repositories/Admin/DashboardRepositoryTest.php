<?php

namespace Tests\Unit\Repositories\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\Event;
use App\Repositories\Admin\DashboardRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DashboardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->repository = new DashboardRepository;
    }

    public function test_get_total_users_returns_correct_count(): void
    {
        User::factory()->count(7)->create();

        $count = $this->repository->getTotalUsers();

        $this->assertEquals(7, $count);
    }

    public function test_get_active_users_returns_correct_count(): void
    {
        User::factory()->count(4)->create(['is_active' => true]);
        User::factory()->count(2)->create(['is_active' => false]);

        $count = $this->repository->getActiveUsers();

        $this->assertEquals(4, $count);
    }

    public function test_get_inactive_users_returns_correct_count(): void
    {
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->count(5)->create(['is_active' => false]);

        $count = $this->repository->getInactiveUsers();

        $this->assertEquals(5, $count);
    }

    public function test_get_total_projects_returns_correct_count(): void
    {
        Project::factory()->count(12)->create();

        $count = $this->repository->getTotalProjects();

        $this->assertEquals(12, $count);
    }

    public function test_get_dashboard_stats_returns_complete_array(): void
    {
        User::factory()->count(6)->create(['is_active' => true]);
        User::factory()->count(4)->create(['is_active' => false]);
        Project::factory()->count(15)->create();

        $stats = $this->repository->getDashboardStats();

        $this->assertArrayHasKey('total_users', $stats);
        $this->assertArrayHasKey('active_users', $stats);
        $this->assertArrayHasKey('inactive_users', $stats);
        $this->assertArrayHasKey('total_projects', $stats);

        $this->assertEquals($this->repository->getTotalUsers(), $stats['total_users']);
        $this->assertEquals($this->repository->getActiveUsers(), $stats['active_users']);
        $this->assertEquals($this->repository->getInactiveUsers(), $stats['inactive_users']);
        $this->assertEquals($this->repository->getTotalProjects(), $stats['total_projects']);
    }

    public function test_get_dashboard_stats_with_no_data(): void
    {
        $stats = $this->repository->getDashboardStats();

        $this->assertEquals([
            'total_users' => 0,
            'active_users' => 0,
            'inactive_users' => 0,
            'total_projects' => 0,
        ], $stats);
    }
}
