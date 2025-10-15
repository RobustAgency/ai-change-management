<?php

namespace App\Repositories\Admin;

use App\Models\User;
use App\Models\Project;

class DashboardRepository
{
    /**
     * Get total users count.
     */
    public function getTotalUsers(): int
    {
        return User::count();
    }

    /**
     * Get active users count.
     */
    public function getActiveUsers(): int
    {
        return User::where('is_active', true)->count();
    }

    /**
     * Get inactive users count.
     */
    public function getInactiveUsers(): int
    {
        return User::where('is_active', false)->count();
    }

    /**
     * Get total projects count across all users.
     */
    public function getTotalProjects(): int
    {
        return Project::count();
    }

    /**
     * Get all admin dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        return [
            'total_users' => $this->getTotalUsers(),
            'active_users' => $this->getActiveUsers(),
            'inactive_users' => $this->getInactiveUsers(),
            'total_projects' => $this->getTotalProjects(),
        ];
    }
}
