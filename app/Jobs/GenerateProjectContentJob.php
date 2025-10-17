<?php

namespace App\Jobs;

use Throwable;
use App\Models\Project;
use App\Enums\ProjectStatus;
use Illuminate\Bus\Queueable;
use App\Enums\ProjectContentStatus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Project\ProjectContentGenerator;

class GenerateProjectContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Project $project) {}

    /**
     * Execute the job.
     */
    public function handle(ProjectContentGenerator $contentGenerator): void
    {
        \info('GenerateProjectContentJob dispatched: '.$this->project->id);
        try {
            $contentGenerator->generateContent($this->project);

            $this->project->update([
                'status' => ProjectStatus::Completed,
                'content_generation_status' => ProjectContentStatus::Completed,
            ]);

            \info('Project content generation completed', ['project_id' => $this->project->id]);
        } catch (Throwable $e) {
            $this->project->update([
                'status' => ProjectStatus::Approved,
                'content_generation_status' => ProjectContentStatus::Failed,
            ]);

            \logger()->error('Project content generation failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the project for this job.
     */
    public function getProject(): Project
    {
        return $this->project;
    }
}
