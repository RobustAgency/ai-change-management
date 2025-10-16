<?php

namespace Tests\Feature\Services\Project\Pipelines;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use App\Services\Project\Pipelines\GenerateSlides;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GenerateSlidesTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_slides_content_successfully(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'template_id' => 1,
            'name' => 'Test Project',
        ]);

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

        $pipeline = app(GenerateSlides::class);
        $result = $pipeline->handle($project, $nextCallback);

        $this->assertSame($project, $result);

        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $this->assertArrayHasKey('executive_summary_slide', $aiContent->slides_content);
        $this->assertEquals('Test project overview', $aiContent->slides_content['executive_summary_slide']['project_overview']);
    }

    public function test_uses_correct_template_based_on_template_id(): void
    {
        $user = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::USER]);
        $project = \App\Models\Project::factory()->create([
            'user_id' => $user->id,
            'template_id' => 3,
            'name' => 'Test Project',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(['slides_content' => ['test' => 'content']]),
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

        $pipeline = app(GenerateSlides::class);
        $pipeline->handle($project, $nextCallback);
    }
}
