<?php

namespace Modules\Settings\Notifications;

use App\Core\Tenant\TenantContext;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Settings\Services\StaffService;

/**
 * Sent synchronously so the link is built from the shop's own domain, not a queue worker's.
 */
class StaffInvitationNotification extends Notification
{
    public function __construct(
        public readonly string $token,
        public readonly string $inviterName,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $shopName = app(TenantContext::class)->get()?->name ?? config('app.name');
        $roles = $notifiable->roles()->pluck('name')->implode(', ');

        return (new MailMessage)
            ->subject("{$this->inviterName} invited you to join {$shopName}")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->inviterName} has added you to the {$shopName} team".($roles !== '' ? " as {$roles}." : '.'))
            ->line('Choose a password to activate your account and sign in.')
            ->action('Accept invitation', route('invitation.show', $this->token))
            ->line('This invitation expires in '.StaffService::INVITATION_VALID_DAYS.' days.')
            ->line('Not expecting this? You can ignore this email and nothing will happen.')
            ->salutation("— {$shopName}");
    }
}
