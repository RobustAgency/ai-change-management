<?php

namespace App\Services\Project;

use App\Clients\OpenAi;
use App\Models\Project;

class ProjectContentGenerator
{
    public function __construct(
        private OpenAi $openAi,
        private ProjectPromptBuilder $promptBuilder
    ) {}

    /**
     * Generate structured content for a Project using the LLM.
     */
    public function generateForProject(Project $project): array
    {
        \info('Generating content for project ID: '.$project->id);
        $prompt = $this->promptBuilder->build($project);

        $messages = [
            ['role' => 'system', 'content' => $prompt],
        ];

        $content = $this->openAi->chat($messages);
        \info('The response from ai is: '.$content);
        $parsed = json_decode($content, true);
        \info('Parsed content for project ID: '.$project->id.': '.json_encode($parsed));
        if (! is_array($parsed)) {
            $parsed = $this->extractJson($content);
        }

        $keyMessages = $parsed['key_messages'] ?? [];
        $audienceVariations = $parsed['audience_variations'] ?? [];
        \info('Extracted key messages for project ID: '.$project->id.': '.json_encode($keyMessages));
        \info('Extracted audience variations for project ID: '.$project->id.': '.json_encode($audienceVariations));

        return [
            'prompt' => $prompt,
            'key_messages' => $keyMessages,
            'audience_variations' => $audienceVariations,
        ];
    }

    /**
     * Try to extract JSON block from a text.
     */
    private function extractJson(?string $text): array
    {
        if (empty($text)) {
            return [];
        }

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            $json = $matches[0];

            return json_decode($json, true) ?: [];
        }

        return [];
    }
}
