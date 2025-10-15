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
use App\Services\Project\Pipelines\GenerateVideoScript;

class GenerateVideoScriptTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_generates_video_script_content_successfully(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Digital Transformation',
            'template_id' => 1,
            'summary' => 'Implementing new digital tools',
            'business_goals' => 'Improve efficiency and modernize processes',
        ]);

        $project->aiContent()->create([
            'project_id' => $project->id,
            'slides_content' => [],
            'emails' => [],
            'faqs' => [],
            'video_script' => [],
        ]);

        $this->mockOpenAi();
        $this->mockViews();

        $nextCallback = function ($proj) {
            return $proj;
        };

        $pipeline = app(GenerateVideoScript::class);
        $result = $pipeline->handle($project, $nextCallback);

        $this->assertSame($project, $result);

        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $this->assertNotEmpty($aiContent->video_script);
        $this->assertIsArray($aiContent->video_script);
        $this->assertArrayHasKey('scene', $aiContent->video_script[0]);
        $this->assertArrayHasKey('narration', $aiContent->video_script[0]);
        $this->assertEquals(1, $aiContent->video_script[0]['scene']);
        $this->assertStringContainsString('Welcome to our project', $aiContent->video_script[0]['narration']);
    }

    public function test_handles_complex_project_data(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Enterprise System Upgrade',
            'template_id' => 2,
            'stakeholders' => [
                ['department' => 'IT', 'role_level' => 'Manager'],
                ['department' => 'Operations', 'role_level' => 'Director'],
            ],
            'launch_date' => now()->addMonths(6),
            'sponsor_name' => 'John Doe',
            'sponsor_title' => 'CTO',
            'business_goals' => 'Modernize legacy systems',
            'expected_outcomes' => 'Improved performance and reliability',
        ]);

        $project->aiContent()->create([
            'project_id' => $project->id,
            'slides_content' => [],
            'emails' => [],
            'faqs' => [],
            'video_script' => [],
        ]);

        $this->mockOpenAiWithMultipleScenes();
        $this->mockViews();

        $nextCallback = function ($proj) {
            return $proj;
        };

        $pipeline = app(GenerateVideoScript::class);
        $result = $pipeline->handle($project, $nextCallback);

        $this->assertSame($project, $result);

        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $this->assertCount(4, $aiContent->video_script);

        foreach ($aiContent->video_script as $scene) {
            $this->assertArrayHasKey('scene', $scene);
            $this->assertArrayHasKey('type', $scene);
            $this->assertArrayHasKey('narration', $scene);
            $this->assertArrayHasKey('visuals', $scene);
            $this->assertArrayHasKey('duration', $scene);
            $this->assertNotEmpty($scene['narration']);
            $this->assertNotEmpty($scene['visuals']);
        }
    }

    public function test_parse_video_script_handles_json_with_code_blocks(): void
    {
        $responseWithCodeBlocks = '```json
        {
            "video_script": [
                {
                    "scene": 1,
                    "type": "introduction",
                    "narration": "Welcome to our transformation journey...",
                    "visuals": "Company logo with animated text",
                    "duration": 8
                },
                {
                    "scene": 2,
                    "type": "problem_statement",
                    "narration": "Our current challenges include...",
                    "visuals": "Charts showing current pain points",
                    "duration": 12
                }
            ]
        }
        ```';

        $testPipeline = app(GenerateVideoScript::class);

        $reflection = new \ReflectionClass($testPipeline);
        $method = $reflection->getMethod('parseVideoScriptFromResponse');
        $method->setAccessible(true);

        $result = $method->invoke($testPipeline, $responseWithCodeBlocks);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['scene']);
        $this->assertEquals('introduction', $result[0]['type']);
        $this->assertEquals('Welcome to our transformation journey...', $result[0]['narration']);
        $this->assertEquals('Company logo with animated text', $result[0]['visuals']);
        $this->assertEquals(8, $result[0]['duration']);
    }

    private function mockOpenAi(): void
    {
        $mockResponse = [
            'video_script' => [
                [
                    'scene' => 1,
                    'type' => 'introduction',
                    'narration' => 'Welcome to our project that will revolutionize how we work together.',
                    'visuals' => 'Company logo and project title slide',
                    'duration' => 10,
                ],
                [
                    'scene' => 2,
                    'type' => 'overview',
                    'narration' => 'This digital transformation will enhance our capabilities and efficiency.',
                    'visuals' => 'Process flow diagrams and statistics',
                    'duration' => 15,
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

    private function mockOpenAiWithMultipleScenes(): void
    {
        $mockResponse = [
            'video_script' => [
                [
                    'scene' => 1,
                    'type' => 'introduction',
                    'narration' => 'Welcome to our Enterprise System Upgrade initiative.',
                    'visuals' => 'Corporate branding and project logo',
                    'duration' => 8,
                ],
                [
                    'scene' => 2,
                    'type' => 'current_state',
                    'narration' => 'Our legacy systems have served us well, but it\'s time for modernization.',
                    'visuals' => 'Screenshots of current systems',
                    'duration' => 12,
                ],
                [
                    'scene' => 3,
                    'type' => 'future_state',
                    'narration' => 'The new system will provide enhanced performance and reliability.',
                    'visuals' => 'Mockups of new system interfaces',
                    'duration' => 15,
                ],
                [
                    'scene' => 4,
                    'type' => 'call_to_action',
                    'narration' => 'Join us on this exciting journey toward digital excellence.',
                    'visuals' => 'Team photos and timeline graphics',
                    'duration' => 10,
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
            ->with('prompts.video_script', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('render')
            ->andReturn('Mocked video script prompt content');
    }
}
