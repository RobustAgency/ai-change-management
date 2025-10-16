<?php

namespace Tests\Feature\Services\Project;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use App\Events\ProjectContentGenerated;
use Illuminate\Support\Facades\Notification;
use App\Services\Project\ProjectContentGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\User\ProjectContentGeneratedNotification;

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
        $generator->generateContent($project);

        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $this->assertNotEmpty($aiContent->slides_content);
        $this->assertNotEmpty($aiContent->emails);
        $this->assertNotEmpty($aiContent->faqs);
        $this->assertNotEmpty($aiContent->video_script);
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
            $generator->generateContent($project);

            $project->refresh();
            $aiContent = $project->aiContent;

            $this->assertNotNull($aiContent);
            $this->assertNotEmpty($aiContent->slides_content);
            $this->assertNotEmpty($aiContent->emails);
            $this->assertNotEmpty($aiContent->faqs);
            $this->assertNotEmpty($aiContent->video_script);
        }
    }

    public function test_generate_content_initializes_generated_content_property(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'template_id' => 1,
        ]);

        $this->assertNull($project->aiContent);

        $this->mockOpenAi();
        $this->mockViews();

        $generator = app(ProjectContentGenerator::class);
        $generator->generateContent($project);

        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $this->assertNotEmpty($aiContent->slides_content);
        $this->assertNotEmpty($aiContent->emails);
        $this->assertNotEmpty($aiContent->faqs);
        $this->assertNotEmpty($aiContent->video_script);
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
        $generator->generateContent($project);

        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);

        $this->assertArrayHasKey('executive_summary_slide', $aiContent->slides_content);
        $this->assertArrayHasKey('benefits_slide', $aiContent->slides_content);

        $this->assertArrayHasKey('Sales', $aiContent->emails);
        $this->assertArrayHasKey('IT', $aiContent->emails);
        $this->assertArrayHasKey('HR', $aiContent->emails);
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
        $generator->generateContent($project);

        $project->refresh();
        $aiContent = $project->aiContent;

        $this->assertNotNull($aiContent);
        $this->assertNotEmpty($aiContent->slides_content);
        $this->assertNotEmpty($aiContent->emails);

        $slidesContent = $aiContent->slides_content;
        $this->assertArrayHasKey('executive_summary_slide', $slidesContent);
        $this->assertArrayHasKey('benefits_slide', $slidesContent);

        $emails = $aiContent->emails;
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

        $mockFaqsResponse = [
            'faqs' => [
                [
                    'question' => 'What is this project about?',
                    'answer' => 'This project aims to improve our processes...',
                ],
                [
                    'question' => 'When will it be launched?',
                    'answer' => 'The project is scheduled to launch next quarter...',
                ],
            ],
        ];

        $mockVideoScriptResponse = [
            'video_script' => [
                [
                    'scene' => 1,
                    'type' => 'introduction',
                    'narration' => 'Welcome to our exciting new project...',
                    'visuals' => 'Company logo and project title',
                    'duration' => 10,
                ],
                [
                    'scene' => 2,
                    'type' => 'overview',
                    'narration' => 'This project will transform how we work...',
                    'visuals' => 'Process diagrams and flowcharts',
                    'duration' => 15,
                ],
            ],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => function ($request) use ($mockSlidesResponse, $mockEmailResponse, $mockFaqsResponse, $mockVideoScriptResponse) {
                static $callCount = 0;
                $callCount++;

                if ($callCount % 4 === 1) {
                    return Http::response([
                        'choices' => [
                            [
                                'message' => [
                                    'content' => json_encode($mockSlidesResponse),
                                ],
                            ],
                        ],
                    ], 200);
                } elseif ($callCount % 4 === 2) {
                    return Http::response([
                        'choices' => [
                            [
                                'message' => [
                                    'content' => json_encode($mockEmailResponse),
                                ],
                            ],
                        ],
                    ], 200);
                } elseif ($callCount % 4 === 3) {
                    return Http::response([
                        'choices' => [
                            [
                                'message' => [
                                    'content' => json_encode($mockFaqsResponse),
                                ],
                            ],
                        ],
                    ], 200);
                } else {
                    if ($callCount % 4 === 0) {
                        $callCount = 0;
                    }

                    return Http::response([
                        'choices' => [
                            [
                                'message' => [
                                    'content' => json_encode($mockVideoScriptResponse),
                                ],
                            ],
                        ],
                    ], 200);
                }
            },
        ]);

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

        View::shouldReceive('make')
            ->with('prompts.faqs', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('make')
            ->with('prompts.video_script', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('make')
            ->with('emails.user.project-content-generated', \Mockery::type('array'))
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

        View::shouldReceive('make')
            ->with('prompts.faqs', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('make')
            ->with('prompts.video_script', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('make')
            ->with('emails.user.project-content-generated', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('render')
            ->andReturn('Mocked prompt content for template '.$templateId);
    }

    public function test_project_content_generator_dispatches_event_on_success(): void
    {
        Event::fake();

        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->mockOpenAi();
        $this->mockViews();

        $generator = app(ProjectContentGenerator::class);
        $generator->generateContent($project);

        Event::assertDispatched(ProjectContentGenerated::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    }

    public function test_project_content_generated_event_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => UserRole::USER]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        View::shouldReceive('make')
            ->with('emails.user.project-content-generated', \Mockery::type('array'))
            ->andReturnSelf();

        View::shouldReceive('render')
            ->andReturn('<html>Mocked email content</html>');

        ProjectContentGenerated::dispatch($project);

        Notification::assertSentTo(
            $user,
            ProjectContentGeneratedNotification::class,
            function ($notification) use ($project) {
                return $notification->project->id === $project->id;
            }
        );
    }
}
