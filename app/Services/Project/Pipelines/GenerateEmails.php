<?php

namespace App\Services\Project\Pipelines;

use Closure;
use Exception;
use App\Clients\OpenAi;
use App\Models\Project;
use Illuminate\Support\Facades\View;

class GenerateEmails
{
    public function __construct(private OpenAi $openAi) {}

    public function handle(Project $project, Closure $next): Project
    {
        \info('Generating emails for project', ['project_id' => $project->id]);
        $prompt = View::make('prompts.emails', compact('project'))->render();

        $response = $this->openAi->chat([['role' => 'system', 'content' => $prompt]]);
        $emails = $this->parseEmailsFromResponse($response);

        if (empty($emails)) {
            throw new Exception('Failed to generate email content.');
        }

        $project->aiContent()->update([
            'emails' => $emails,
        ]);

        return $next($project);
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
