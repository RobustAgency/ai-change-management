<?php

namespace App\Services\Project\Pipelines;

use Closure;
use Exception;
use App\Clients\OpenAi;
use App\Models\Project;
use Illuminate\Support\Facades\View;

class GenerateVideoScript
{
    public function __construct(private OpenAi $openAi) {}

    /**
     * Handle video script generation for a project.
     */
    public function handle(Project $project, Closure $next): Project
    {
        \info('Generating video script for project', ['project_id' => $project->id]);
        $prompt = View::make('prompts.video_script', compact('project'))->render();

        $response = $this->openAi->chat([
            ['role' => 'system', 'content' => $prompt],
        ]);

        $videoScript = $this->parseVideoScriptFromResponse($response);

        if (empty($videoScript)) {
            throw new Exception('Failed to generate video script.');
        }

        $project->aiContent()->update([
            'video_script' => $videoScript,
        ]);

        return $next($project);
    }

    /**
     * Extract and decode structured JSON from the AI response.
     */
    private function parseVideoScriptFromResponse(string $response): array
    {
        $cleanResponse = $this->removeCodeFences($response);
        $jsonData = $this->decodeToArray($cleanResponse);

        return $jsonData['video_script'] ?? [];
    }

    private function removeCodeFences(string $text): string
    {
        return preg_replace('/```(?:json)?\s*|\s*```/m', '', $text);
    }

    private function decodeToArray(string $text): array
    {
        $decoded = json_decode($text, true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }
}
