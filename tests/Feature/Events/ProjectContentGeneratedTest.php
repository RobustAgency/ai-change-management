<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Support\Facades\Event;
use App\Events\ProjectContentGenerated;
use Illuminate\Support\Facades\Notification;
use App\Listeners\User\SendProjectContentGeneratedNotification;
use App\Notifications\User\ProjectContentGeneratedNotification;

class ProjectContentGeneratedTest extends TestCase
{
    public function test_event_can_be_dispatched(): void
    {
        Event::fake();

        $user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => UserRole::USER,
        ]);

        $project = new Project([
            'name' => 'Test Project',
        ]);
        $project->id = 1;
        $project->setRelation('user', $user);

        ProjectContentGenerated::dispatch($project);

        Event::assertDispatched(ProjectContentGenerated::class, function ($event) use ($project) {
            return $event->project->id === $project->id &&
                   $event->project->name === $project->name;
        });
    }

    public function test_listener_sends_notification_when_event_is_dispatched(): void
    {
        Notification::fake();

        $user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => UserRole::USER,
        ]);

        $project = new Project([
            'name' => 'Test Project',
        ]);
        $project->id = 1;
        $project->setRelation('user', $user);

        $listener = new SendProjectContentGeneratedNotification;
        $event = new ProjectContentGenerated($project);

        $listener->handle($event);

        Notification::assertSentTo(
            $user,
            ProjectContentGeneratedNotification::class,
            function ($notification) use ($project) {
                return $notification->project->id === $project->id;
            }
        );
    }
}
