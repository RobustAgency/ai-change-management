<?php

namespace App\Services\Project;

use App\Models\Project;
use Illuminate\Support\Facades\View;

class ProjectPromptBuilder
{
    public function build(Project $project): string
    {
        return View::make('prompts.project_generation', compact('project'))->render();
    }
}
