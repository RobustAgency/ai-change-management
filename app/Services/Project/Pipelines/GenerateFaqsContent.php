<?php

namespace App\Services\Project\Pipelines;

use Closure;
use Exception;
use App\Clients\OpenAi;
use App\Models\Project;
use Illuminate\Support\Facades\View;

class GenerateFaqsContent
{
    public function __construct(private OpenAi $openAi) {}

    public function handle(Project $project, Closure $next): Project
    {
        $prompt = View::make('prompts.faqs', compact('project'))->render();

        $response = $this->openAi->chat([['role' => 'system', 'content' => $prompt]]);

        $faqs = $this->parseFaqsFromResponse($response);

        if (empty($faqs)) {
            throw new Exception('Failed to generate faqs.');
        }

        $project->aiContent()->update([
            'faqs' => $faqs,
        ]);

        return $next($project);
    }

    private function parseFaqsFromResponse(string $response): array
    {
        $cleanResponse = $this->removeCodeBlocks($response);
        $jsonData = $this->decodeJson($cleanResponse);

        return $jsonData['faqs'] ?? [];
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
