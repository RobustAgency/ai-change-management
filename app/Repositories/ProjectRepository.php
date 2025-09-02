<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository
{
    /**
     * Get paginated list of users with specified relations.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Project>
     */
    public function getFilteredProjects(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Project::where('user_id', $user->id);

        if (! empty($filters['term'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['term']}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['launch_date'])) {
            $query->whereDate('launch_date', $filters['launch_date']);
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function createForUser(User $user, array $projectData): Project
    {
        $projectData['user_id'] = $user->id;

        return Project::create($projectData);
    }

    public function update(Project $project, array $projectData): Project
    {
        $project->update($projectData);

        return $project;
    }

    public function delete(Project $project): bool
    {
        return $project->delete();
    }
}
