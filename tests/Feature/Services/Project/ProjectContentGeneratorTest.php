<?php

namespace Tests\Feature\Services\Project;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use App\Services\Project\ProjectContentGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectContentGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private ProjectContentGenerator $generator;

    public function test_generate_content_creates_slides_and_email_content(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'template_id' => 1,
            'name' => 'CRM Implementation',
            'summary' => 'Implementing new CRM system for better customer management',
            'business_goals' => 'Improve customer service and sales efficiency',
            'expected_outcomes' => 'Better lead tracking and customer satisfaction',
        ]);

        $this->mockOpenAi();
        $this->mockViews();

        $generator = app(ProjectContentGenerator::class);
        $result = $generator->generateContent($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('slides_content', $result);
        $this->assertArrayHasKey('emails', $result);
        $this->assertNotEmpty($result['slides_content']);
        $this->assertNotEmpty($result['emails']);
    }

    public function test_generate_content_with_different_template_ids(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);

        foreach ([1, 2, 3] as $templateId) {
            $project = Project::factory()->create([
                'user_id' => $user->id,
                'template_id' => $templateId,
                'name' => "Test Project Template {$templateId}",
            ]);

            $this->mockOpenAi();
            $this->mockViewsForTemplate($templateId);

            $generator = app(ProjectContentGenerator::class);
            $result = $generator->generateContent($project);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('slides_content', $result);
            $this->assertArrayHasKey('emails', $result);
        }
    }

    public function test_generate_content_initializes_generated_content_property(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'template_id' => 1,
        ]);

        $this->assertNull($project->generated_content);

        $this->mockOpenAi();
        $this->mockViews();

        $generator = app(ProjectContentGenerator::class);
        $generator->generateContent($project);

        $this->assertIsArray($project->generated_content);
        $this->assertArrayHasKey('slides_content', $project->generated_content);
        $this->assertArrayHasKey('emails', $project->generated_content);
    }

    public function test_generate_content_handles_complex_project_data(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'template_id' => 2,
            'stakeholders' => [
                ['department' => 'IT', 'role_level' => 'Manager'],
                ['department' => 'Sales', 'role_level' => 'Director'],
                ['department' => 'HR', 'role_level' => 'C-Suite'],
            ],
            'launch_date' => now()->addMonths(3),
            'sponsor_name' => 'Jane Smith',
            'sponsor_title' => 'VP of Operations',
        ]);

        $this->mockOpenAi();
        $this->mockViews();

        $generator = app(ProjectContentGenerator::class);
        $result = $generator->generateContent($project);

        $this->assertIsArray($result);

        $this->assertArrayHasKey('executive_summary_slide', $result['slides_content']);
        $this->assertArrayHasKey('benefits_slide', $result['slides_content']);

        $this->assertArrayHasKey('Sales', $result['emails']);
        $this->assertArrayHasKey('IT', $result['emails']);
        $this->assertArrayHasKey('HR', $result['emails']);
    }

    public function test_generate_content_returns_consistent_structure(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'template_id' => 3,
        ]);

        $this->mockOpenAi();
        $this->mockViews();

        $generator = app(ProjectContentGenerator::class);
        $result = $generator->generateContent($project);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('slides_content', $result);
        $this->assertArrayHasKey('emails', $result);

        $slidesContent = $result['slides_content'];
        $this->assertArrayHasKey('executive_summary_slide', $slidesContent);
        $this->assertArrayHasKey('benefits_slide', $slidesContent);

        $emails = $result['emails'];
        $this->assertIsArray($emails);
        foreach ($emails as $department => $emailData) {
            $this->assertArrayHasKey('subject', $emailData);
            $this->assertArrayHasKey('body', $emailData);
        }
    }

    private function mockOpenAi(): void
    {
        $mockSlidesResponse = [
            'slides_content' => [
                'executive_summary_slide' => [
                    'project_overview' => 'Test project overview',
                    'purpose_of_ocm_plan' => 'Test OCM purpose',
                    'aligned_with_org_mission_and_vision' => ['Mission alignment'],
                    'benefits' => ['Benefit 1', 'Benefit 2'],
                    'strategic_objectives_of_ocm_plan' => ['Objective 1', 'Objective 2'],
                ],
                'benefits_slide' => [
                    'heading' => 'Key Benefits',
                    'benefit_cards' => [
                        ['title' => 'Efficiency', 'bullet_list' => ['Point 1', 'Point 2']],
                    ],
                ],
                'key_stakeholders_slide' => [
                    'heading' => 'Key Stakeholders',
                    'stakeholder_table' => [
                        ['title' => 'Project Manager', 'project_role' => 'Leads the project'],
                    ],
                ],
                'high_level_change_management_strategy_slide' => [
                    'heading' => 'Change Management Strategy',
                    'stakeholder_alignment_and_engagement' => [
                        'title' => 'Stakeholder Engagement',
                        'actions' => ['Action 1', 'Action 2'],
                    ],
                ],
            ],
        ];

        $mockEmailResponse = [
            'emails' => [
                'Sales' => [
                    'subject' => 'Exciting Changes Ahead for Sales',
                    'body' => 'Dear Sales Team,\\n\\nWe are implementing new systems...\\n\\nBest regards,\\n[Name]',
                ],
                'IT' => [
                    'subject' => 'Technical Implementation Update',
                    'body' => 'Dear IT Team,\\n\\nYour expertise is crucial for this project...\\n\\nSincerely,\\n[Name]',
                ],
                'HR' => [
                    'subject' => 'Employee Communication Strategy',
                    'body' => 'Dear HR Team,\\n\\nPlease help us communicate these changes...\\n\\nThank you,\\n[Name]',
                ],
            ],
        ];

        // Mock HTTP responses for both slides and email generation
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => function ($request) use ($mockSlidesResponse, $mockEmailResponse) {
                static $callCount = 0;
                $callCount++;

                // First call returns slides, second call returns emails
                if ($callCount % 2 === 1) {
                    return Http::response([
                        'choices' => [
                            [
                                'message' => [
                                    'content' => json_encode($mockSlidesResponse),
                                ],
                            ],
                        ],
                    ], 200);
                } else {
                    $callCount = 0; // Reset for next test

                    return Http::response([
                        'choices' => [
                            [
                                'message' => [
                                    'content' => json_encode($mockEmailResponse),
                                ],
                            ],
                        ],
                    ], 200);
                }
            },
        ]);

        // Create generator AFTER setting up the HTTP fake
        $this->generator = app(ProjectContentGenerator::class);
    }

    private function mockViews(): void
    {
        View::shouldReceive('make')
            ->with(\Mockery::pattern('/prompts\.slides_template_\d+/'), \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('make')
            ->with('prompts.emails', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('render')
            ->andReturn('Mocked prompt content');
    }

    private function mockViewsForTemplate(int $templateId): void
    {
        View::shouldReceive('make')
            ->with("prompts.slides_template_{$templateId}", \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('make')
            ->with('prompts.emails', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('render')
            ->andReturn('Mocked prompt content for template '.$templateId);
    }
}
