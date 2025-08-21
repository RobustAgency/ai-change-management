<?php

namespace App\Listeners\User;

use App\Events\UserApprovalRevoked;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\User\AccountRevokedNotification;

class SendAccountRevokedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UserApprovalRevoked $event): void
    {
        $user = $event->user;
        $user->notify(new AccountRevokedNotification);
    }
}
