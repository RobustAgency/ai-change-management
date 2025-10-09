<?php

namespace App\Services\Project\Pipelines;

use Closure;
use App\Clients\OpenAi;
use App\Models\Project;
use Illuminate\Support\Facades\View;

class GenerateSlidesContent
{
    public function __construct(private OpenAi $openAi) {}

    public function handle(Project $project, Closure $next): Project
    {
        $view = 'prompts.slides_template_'.($project->template_id ?? 1);

        $prompt = View::make($view, compact('project'))->render();

        $response = $this->openAi->chat([['role' => 'system', 'content' => $prompt]]);

        $data = json_decode($response, true) ?: $this->extractJson($response);

        $project->generated_content['slides_content'] = $data['slides_content'] ?? [];

        return $next($project);
    }

    private function extractJson(?string $text): array
    {
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            return json_decode($matches[0], true) ?: [];
        }

        return [];
    }
}
