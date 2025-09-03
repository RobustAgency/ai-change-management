<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class OpenAi
{
    protected PendingRequest $http;

    private string $version = 'v1';

    public function __construct()
    {
        $this->http = Http::acceptJson()
            ->withToken(\config('services.openai.secret'))
            ->connectTimeout(120)
            ->timeout(120)
            ->baseUrl("https://api.openai.com/{$this->version}");
    }

    public function chat(array $messages, string $model = 'gpt-4-turbo'): string
    {
        $response = $this->http->post('/chat/completions', \compact('model', 'messages'));
        $responseData = $response->json();

        if (! isset($responseData['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid OpenAI response structure: '.json_encode($responseData));
        }

        return $responseData['choices'][0]['message']['content'];

    }
}
