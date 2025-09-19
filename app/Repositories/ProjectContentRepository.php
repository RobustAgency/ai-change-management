<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\ProjectContent;

class ProjectContentRepository
{
    public function upsertForProject(Project $project, array $data): ProjectContent
    {
        return ProjectContent::updateOrCreate(
            ['project_id' => $project->id],
            [
                'slides_content' => $data['slides_content'],
                // 'faqs' => $data['faqs'],
                // 'video_script' => $data['video_script'],
            ]
        );
    }

    /**
     * Get ProjectContent for a given project.
     */
    public function getForProject(Project $project): ?ProjectContent
    {
        return $project->aiContent()->first();
    }
}
