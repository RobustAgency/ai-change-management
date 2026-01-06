<?php

namespace App\Listeners\User;

use App\Events\ProjectContentGenerated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\User\ProjectContentGeneratedNotification;

class SendProjectContentGeneratedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ProjectContentGenerated $event): void
    {
        \info('Sending project content generated notification for project: '.$event->project->id);
        $project = $event->project;
        $user = $project->user;

        $user->notify(new ProjectContentGeneratedNotification($project));
    }
}
