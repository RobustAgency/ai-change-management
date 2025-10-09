<?php

namespace App\Services\Project\Pipelines;

use Closure;
use App\Clients\OpenAi;
use App\Models\Project;
use Illuminate\Support\Facades\View;

class GenerateEmailContent
{
    public function __construct(private OpenAi $openAi) {}

    public function handle(Project $project, Closure $next): Project
    {
        $prompt = $this->buildPrompt($project);

        $response = $this->openAi->chat([['role' => 'system', 'content' => $prompt]]);
        $emails = $this->parseEmailsFromResponse($response);

        $project->generated_content['emails'] = $emails;

        return $next($project);
    }

    private function buildPrompt(Project $project): string
    {
        return View::make('prompts.emails', compact('project'))->render();
    }

    private function parseEmailsFromResponse(string $response): array
    {
        $cleanResponse = $this->removeCodeBlocks($response);
        $jsonData = $this->decodeJson($cleanResponse);

        return $jsonData['emails'] ?? [];
    }

    private function removeCodeBlocks(string $response): string
    {
        return preg_replace('/```(?:json)?\s*|\s*```/m', '', $response);
    }

    private function decodeJson(string $jsonString): array
    {
        $decoded = json_decode($jsonString, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [];
    }
}
