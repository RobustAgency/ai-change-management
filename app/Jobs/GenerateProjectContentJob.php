<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\ProjectContentRepository;
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
    public function handle(ProjectContentGenerator $contentGenerator, ProjectContentRepository $repository): void
    {
        \info('Generating project content for project: '.$this->project->id);
        $data = $contentGenerator->generateForProject($this->project);

        $repository->upsertForProject($this->project, $data);
    }
}
