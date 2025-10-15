<?php

namespace Tests\Feature\Services\Project\Pipelines;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Project\Pipelines\GenerateFaqsContent;

class GenerateFaqsContentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_generates_faqs_content_successfully(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'CRM Implementation',
            'template_id' => 1,
            'summary' => 'Implementing new CRM system',
            'business_goals' => 'Improve customer relationships',
        ]);

        // Create the aiContent record first since FaqsContent pipeline uses update() not updateOrCreate()
        $project->aiContent()->create([
            'project_id' => $project->id,
            'slides_content' => [],
            'emails' => [],
            'faqs' => [],
        ]);

        $this->mockOpenAi();
        $this->mockViews();

        $nextCallback = function ($proj) {
            return $proj;
        };

        $pipeline = app(GenerateFaqsContent::class);
        $result = $pipeline->handle($project, $nextCallback);

        $this->assertSame($project, $result);

        // Refresh the project to get the updated aiContent
        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $this->assertNotEmpty($aiContent->faqs);
        $this->assertIsArray($aiContent->faqs);
        $this->assertArrayHasKey('question', $aiContent->faqs[0]);
        $this->assertArrayHasKey('answer', $aiContent->faqs[0]);
        $this->assertEquals('What is this project about?', $aiContent->faqs[0]['question']);
        $this->assertStringContainsString('CRM system', $aiContent->faqs[0]['answer']);
    }

    public function test_handles_complex_project_data(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Digital Transformation Initiative',
            'template_id' => 2,
            'stakeholders' => [
                ['department' => 'IT', 'role_level' => 'Manager'],
                ['department' => 'Sales', 'role_level' => 'Director'],
            ],
            'launch_date' => now()->addMonths(3),
            'sponsor_name' => 'Jane Smith',
            'sponsor_title' => 'VP of Operations',
            'business_goals' => 'Modernize our technology stack',
            'expected_outcomes' => 'Improved efficiency and scalability',
        ]);

        // Create the aiContent record first since FaqsContent pipeline uses update() not updateOrCreate()
        $project->aiContent()->create([
            'project_id' => $project->id,
            'slides_content' => [],
            'emails' => [],
            'faqs' => [],
        ]);

        $this->mockOpenAiWithMultipleFaqs();
        $this->mockViews();

        $nextCallback = function ($proj) {
            return $proj;
        };

        $pipeline = app(GenerateFaqsContent::class);
        $result = $pipeline->handle($project, $nextCallback);

        $this->assertSame($project, $result);

        // Refresh the project to get the updated aiContent
        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $this->assertCount(3, $aiContent->faqs);

        foreach ($aiContent->faqs as $faq) {
            $this->assertArrayHasKey('question', $faq);
            $this->assertArrayHasKey('answer', $faq);
            $this->assertNotEmpty($faq['question']);
            $this->assertNotEmpty($faq['answer']);
        }
    }

    public function test_parse_faqs_handles_json_with_code_blocks(): void
    {
        $responseWithCodeBlocks = '```json
        {
            "faqs": [
                {
                    "question": "What are the benefits of this change?",
                    "answer": "This change will improve efficiency and reduce costs significantly."
                },
                {
                    "question": "When will the changes take effect?",
                    "answer": "The changes are scheduled to be implemented over the next quarter."
                }
            ]
        }
        ```';

        $testPipeline = app(GenerateFaqsContent::class);

        $reflection = new \ReflectionClass($testPipeline);
        $method = $reflection->getMethod('parseFaqsFromResponse');
        $method->setAccessible(true);

        $result = $method->invoke($testPipeline, $responseWithCodeBlocks);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('What are the benefits of this change?', $result[0]['question']);
        $this->assertEquals('When will the changes take effect?', $result[1]['question']);
        $this->assertStringContainsString('improve efficiency', $result[0]['answer']);
        $this->assertStringContainsString('next quarter', $result[1]['answer']);
    }

    private function mockOpenAi(): void
    {
        $mockResponse = [
            'faqs' => [
                [
                    'question' => 'What is this project about?',
                    'answer' => 'This project involves implementing a new CRM system to improve customer relationships and streamline our sales processes.',
                ],
                [
                    'question' => 'When will it be completed?',
                    'answer' => 'The project is expected to be completed by the end of next quarter.',
                ],
            ],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($mockResponse),
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    private function mockOpenAiWithMultipleFaqs(): void
    {
        $mockResponse = [
            'faqs' => [
                [
                    'question' => 'What is the Digital Transformation Initiative?',
                    'answer' => 'This initiative aims to modernize our technology stack and improve operational efficiency.',
                ],
                [
                    'question' => 'How will this affect my daily work?',
                    'answer' => 'The changes will streamline your workflow and provide better tools for collaboration.',
                ],
                [
                    'question' => 'What training will be provided?',
                    'answer' => 'Comprehensive training sessions will be conducted for all affected departments.',
                ],
            ],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($mockResponse),
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    private function mockViews(): void
    {
        View::shouldReceive('make')
            ->with('prompts.faqs', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('render')
            ->andReturn('Mocked FAQ prompt content');
    }
}
