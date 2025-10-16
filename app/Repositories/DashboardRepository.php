<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\User;

class DashboardRepository
{
    /**
     * Get total projects count for a user.
     */
    public function getTotalProjects(User $user): int
    {
        return $user->projects()->count();
    }

    /**
     * Get projects created this month for a user.
     */
    public function getProjectsThisMonth(User $user): int
    {
        return $user->projects()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
    }

    /**
     * Get projects with AI content generated for a user.
     */
    public function getProjectsWithContent(User $user): int
    {
        return $user->projects()
            ->whereHas('aiContent')
            ->count();
    }

    /**
     * Get projects pending AI content generation for a user.
     */
    public function getProjectsPendingGeneration(User $user): int
    {
        return $user->projects()
            ->whereDoesntHave('aiContent')
            ->count();
    }

    /**
     * Get all dashboard statistics for a user.
     */
    public function getDashboardStats(User $user): array
    {
        return [
            'total_projects' => $this->getTotalProjects($user),
            'this_month' => $this->getProjectsThisMonth($user),
            'content_generated' => $this->getProjectsWithContent($user),
            'pending_generation' => $this->getProjectsPendingGeneration($user),
        ];
    }
}
