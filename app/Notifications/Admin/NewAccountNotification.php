<?php

namespace App\Notifications\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Account Registration')
            ->greeting('Hello Admin')
            ->line("A new user ({$this->user->name}) has registered on the platform.")
            ->line("Email: {$this->user->email}")
            ->line("Registered at: {$this->user->created_at->toDayDateTimeString()}")
            ->action('View User', url(\config('app.frontend_url')."/admin/users/{$this->user->id}"))
            ->line('Please review this account for approval.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'registered_at' => $this->user->created_at,
            'message' => 'New user registration',
        ];
    }
}
