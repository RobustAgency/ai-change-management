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
    public function generateContent(Project $project): array
    {
        $prompt = $this->promptBuilder->build($project);

        $messages = [
            ['role' => 'system', 'content' => $prompt],
        ];

        $content = $this->openAi->chat($messages);
        $parsed = json_decode($content, true);

        if (! is_array($parsed)) {
            $parsed = $this->extractJson($content);
        }

        $keyMessages = $parsed['key_messages'] ?? [];
        $audienceVariations = $parsed['audience_variations'] ?? [];

        return [
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
