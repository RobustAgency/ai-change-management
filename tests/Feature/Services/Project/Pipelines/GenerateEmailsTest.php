<?php

namespace Tests\Feature\Services\Project\Pipelines;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\Project\Pipelines\GenerateEmails;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GenerateEmailsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_generates_email_content_successfully(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'CRM Implementation',
            'template_id' => 1,
            'stakeholders' => [
                ['department' => 'Sales', 'role_level' => 'Manager'],
                ['department' => 'HR', 'role_level' => 'Director'],
            ],
        ]);

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

        $pipeline = app(GenerateEmails::class);
        $result = $pipeline->handle($project, $nextCallback);

        $this->assertSame($project, $result);

        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $this->assertArrayHasKey('Sales', $aiContent->emails);
        $this->assertArrayHasKey('HR', $aiContent->emails);
        $this->assertEquals('Exciting Changes Ahead for Sales', $aiContent->emails['Sales']['subject']);
        $this->assertStringContainsString('Dear Sales Team', $aiContent->emails['Sales']['body']);
    }

    public function test_handle_with_complex_stakeholder_structure(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Digital Transformation Initiative',
            'template_id' => 2,
            'stakeholders' => [
                ['department' => 'IT', 'role_level' => 'C-Suite'],
                ['department' => 'Finance', 'role_level' => 'Manager'],
                ['department' => 'Operations', 'role_level' => 'Director'],
                ['department' => 'Marketing', 'role_level' => 'Manager'],
            ],
            'launch_date' => now()->addMonths(6),
            'sponsor_name' => 'John Doe',
            'sponsor_title' => 'CEO',
        ]);

        $project->aiContent()->create([
            'project_id' => $project->id,
            'slides_content' => [],
            'emails' => [],
            'faqs' => [],
        ]);

        $this->mockOpenAiWithMultipleDepartments();
        $this->mockViews();

        $nextCallback = function ($proj) {
            return $proj;
        };

        $pipeline = app(GenerateEmails::class);
        $result = $pipeline->handle($project, $nextCallback);

        $this->assertSame($project, $result);

        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $emails = $aiContent->emails;
        $this->assertArrayHasKey('IT', $emails);
        $this->assertArrayHasKey('Finance', $emails);
        $this->assertArrayHasKey('Operations', $emails);
        $this->assertArrayHasKey('Marketing', $emails);

        foreach ($emails as $department => $emailData) {
            $this->assertArrayHasKey('subject', $emailData);
            $this->assertArrayHasKey('body', $emailData);
            $this->assertNotEmpty($emailData['subject']);
            $this->assertNotEmpty($emailData['body']);
        }
    }

    public function test_handles_view_rendering_correctly(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Strategic Initiative',
            'template_id' => 2,
            'stakeholders' => [
                ['department' => 'Sales', 'role_level' => 'Manager'],
            ],
        ]);

        $this->mockOpenAi();

        View::shouldReceive('make')
            ->once()
            ->with('prompts.emails', ['project' => $project])
            ->andReturnSelf();

        View::shouldReceive('render')
            ->once()
            ->andReturn('enhanced prompt content for strategic initiative');

        $nextCallback = function ($proj) {
            return $proj;
        };

        $pipeline = app(GenerateEmails::class);
        $result = $pipeline->handle($project, $nextCallback);

        $this->assertSame($project, $result);
    }

    public function test_parse_emails_handles_json_with_code_blocks(): void
    {
        $responseWithCodeBlocks = '```json
        {
            "emails": {
                "Engineering": {
                    "subject": "Technical Implementation Plan",
                    "body": "Dear Engineering Team,\\n\\nWe are launching a new technical initiative..."
                },
                "QA": {
                    "subject": "Quality Assurance Strategy",
                    "body": "Dear QA Team,\\n\\nYour role in ensuring quality..."
                }
            }
        }
        ```';

        $testPipeline = app(GenerateEmails::class);

        $reflection = new \ReflectionClass($testPipeline);
        $method = $reflection->getMethod('parseEmailsFromResponse');
        $method->setAccessible(true);

        $result = $method->invoke($testPipeline, $responseWithCodeBlocks);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Engineering', $result);
        $this->assertArrayHasKey('QA', $result);
        $this->assertEquals('Technical Implementation Plan', $result['Engineering']['subject']);
        $this->assertEquals('Quality Assurance Strategy', $result['QA']['subject']);
    }

    private function mockOpenAi(): void
    {
        $mockResponse = [
            'emails' => [
                'Sales' => [
                    'subject' => 'Exciting Changes Ahead for Sales',
                    'body' => 'Dear Sales Team,\\n\\nWe are implementing new systems...\\n\\nBest regards,\\n[Name]',
                ],
                'HR' => [
                    'subject' => 'Employee Communication Strategy',
                    'body' => 'Dear HR Team,\\n\\nPlease help us communicate these changes...\\n\\nThank you,\\n[Name]',
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

    private function mockOpenAiWithMultipleDepartments(): void
    {
        $mockResponse = [
            'emails' => [
                'IT' => [
                    'subject' => 'Technical Implementation Update',
                    'body' => 'Dear IT Team,\\n\\nYour expertise is crucial for this project...\\n\\nSincerely,\\n[Name]',
                ],
                'Finance' => [
                    'subject' => 'Budget and Resource Planning',
                    'body' => 'Dear Finance Team,\\n\\nWe need your support for budget planning...\\n\\nBest regards,\\n[Name]',
                ],
                'Operations' => [
                    'subject' => 'Operational Excellence Initiative',
                    'body' => 'Dear Operations Team,\\n\\nThis change will enhance our operations...\\n\\nThank you,\\n[Name]',
                ],
                'Marketing' => [
                    'subject' => 'Marketing Alignment Strategy',
                    'body' => 'Dear Marketing Team,\\n\\nYour creative input is valuable...\\n\\nWarm regards,\\n[Name]',
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

    private function mockOpenAiWithInvalidResponse(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'This is not valid JSON',
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    private function mockOpenAiWithEmptyResponse(): void
    {
        $mockResponse = ['other_content' => 'value'];

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
            ->with('prompts.emails', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('render')
            ->andReturn('Mocked email prompt content');
    }
}
