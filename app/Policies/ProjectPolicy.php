<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;
use App\Enums\ProjectStatus;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can generate AI content for the project.
     */
    public function generateContent(User $user, Project $project): Response
    {
        if ($user->id !== $project->user_id) {
            return Response::deny('You do not own this project.');
        }

        if ($project->aiContent()->exists()) {
            return Response::deny('AI content has already been generated for this project.');
        }

        if ($project->status !== ProjectStatus::Completed) {
            return Response::deny('Project must be completed before generating AI content.');
        }

        return Response::allow();
    }
}
