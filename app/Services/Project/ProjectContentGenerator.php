<?php

namespace App\Services\Project;

use Throwable;
use App\Models\Project;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use App\Services\Project\Pipelines\GenerateFaqsContent;
use App\Services\Project\Pipelines\GenerateEmailContent;
use App\Services\Project\Pipelines\GenerateSlidesContent;

class ProjectContentGenerator
{
    public function __construct(
        private Pipeline $pipeline
    ) {}

    public function generateContent(Project $project): void
    {
        DB::beginTransaction();

        try {
            $this->pipeline
                ->send($project)
                ->through([
                    GenerateSlidesContent::class,
                    GenerateEmailContent::class,
                    GenerateFaqsContent::class,
                    // Future: GenerateFaqsContent::class, GenerateVideoScript::class, etc.
                ])
                ->thenReturn();

            DB::commit();
        } catch (Throwable $th) {
            DB::rollBack();
            \logger()->error('Failed generating project content', ['project_id' => $project->id, 'error' => $th->getMessage()]);
            throw $th;
        }
    }
}
