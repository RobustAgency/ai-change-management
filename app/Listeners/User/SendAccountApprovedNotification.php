<?php

namespace App\Listeners\User;

use App\Events\UserApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\User\AccountApprovedNotification;

class SendAccountApprovedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UserApproved $event): void
    {
        $user = $event->user;
        $user->notify(new AccountApprovedNotification);
    }
}
