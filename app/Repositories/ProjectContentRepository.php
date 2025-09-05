<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\ProjectAiContent;

class ProjectContentRepository
{
    public function upsertForProject(Project $project, array $data): ProjectAiContent
    {
        return ProjectAiContent::updateOrCreate(
            ['project_id' => $project->id],
            [
                'key_messages' => $data['key_messages'] ?? null,
                'audience_variations' => $data['audience_variations'] ?? null,
            ]
        );
    }

    public function getForProject(Project $project): ?ProjectAiContent
    {
        return $project->aiContent()->first();
    }
}
