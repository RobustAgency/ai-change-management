<?php

namespace App\Services\Project;

use Throwable;
use App\Models\Project;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use App\Events\ProjectContentGenerated;
use App\Services\Project\Pipelines\GenerateFaqs;
use App\Services\Project\Pipelines\GenerateEmails;
use App\Services\Project\Pipelines\GenerateSlides;
use App\Services\Project\Pipelines\GenerateVideoScript;

class ProjectContentGenerator
{
    public function __construct(
        private Pipeline $pipeline
    ) {}

    public function generateContent(Project $project): void
    {
        \info('Starting content generation pipeline for project: '.$project->id);
        DB::beginTransaction();

        try {
            $this->pipeline
                ->send($project)
                ->through([
                    GenerateSlides::class,
                    GenerateEmails::class,
                    GenerateFaqs::class,
                    GenerateVideoScript::class,
                ])
                ->thenReturn();

            DB::commit();
            \info('Content generation pipeline completed for project: '.$project->id);
            ProjectContentGenerated::dispatch($project);
        } catch (Throwable $th) {
            DB::rollBack();
            \logger()->error('Failed generating project content', ['project_id' => $project->id, 'error' => $th->getMessage()]);
            throw $th;
        }
    }
}
