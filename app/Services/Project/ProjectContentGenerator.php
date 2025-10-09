<?php

namespace App\Services\Project;

use App\Models\Project;
use Illuminate\Pipeline\Pipeline;
use App\Services\Project\Pipelines\GenerateEmailContent;
use App\Services\Project\Pipelines\GenerateSlidesContent;

class ProjectContentGenerator
{
    public function __construct(
        private Pipeline $pipeline
    ) {}

    public function generateContent(Project $project): array
    {
        $project->generated_content = [];

        $project = $this->pipeline
            ->send($project)
            ->through([
                GenerateSlidesContent::class,
                GenerateEmailContent::class,
            ])
            ->thenReturn();

        return $project->generated_content;
    }
}
