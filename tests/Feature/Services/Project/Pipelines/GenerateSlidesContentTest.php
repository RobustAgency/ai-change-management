<?php

namespace Tests\Feature\Services\Project\Pipelines;

use Tests\TestCase;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use App\Services\Project\Pipelines\GenerateSlidesContent;

class GenerateSlidesContentTest extends TestCase
{
    public function test_generates_slides_content_successfully(): void
    {
        $project = new Project(['template_id' => 1, 'name' => 'Test Project']);
        $project->generated_content = [];

        $mockResponse = json_encode([
            'slides_content' => [
                'executive_summary_slide' => [
                    'project_overview' => 'Test project overview',
                    'benefits' => ['Benefit 1', 'Benefit 2'],
                ],
            ],
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => $mockResponse,
                        ],
                    ],
                ],
            ]),
        ]);

        View::shouldReceive('make')
            ->once()
            ->with('prompts.slides_template_1', ['project' => $project])
            ->andReturnSelf();

        View::shouldReceive('render')
            ->once()
            ->andReturn('rendered prompt content');

        $nextCallback = function ($proj) {
            return $proj;
        };

        $pipeline = app(GenerateSlidesContent::class);
        $result = $pipeline->handle($project, $nextCallback);

        $this->assertSame($project, $result);
        $this->assertArrayHasKey('slides_content', $project->generated_content);
        $this->assertArrayHasKey('executive_summary_slide', $project->generated_content['slides_content']);
        $this->assertEquals('Test project overview', $project->generated_content['slides_content']['executive_summary_slide']['project_overview']);
    }

    public function test_uses_correct_template_based_on_template_id(): void
    {
        $project = new Project(['template_id' => 3, 'name' => 'Test Project']);
        $project->generated_content = [];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '{}',
                        ],
                    ],
                ],
            ]),
        ]);

        View::shouldReceive('make')
            ->once()
            ->with('prompts.slides_template_3', ['project' => $project])
            ->andReturnSelf();

        View::shouldReceive('render')
            ->once()
            ->andReturn('rendered prompt content');

        $nextCallback = function ($proj) {
            return $proj;
        };

        $pipeline = app(GenerateSlidesContent::class);
        $pipeline->handle($project, $nextCallback);
    }
}
