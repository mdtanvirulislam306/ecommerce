<?php

namespace Modules\Ecommerce\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\CustomerAccount;
use Modules\Ecommerce\Services\OrderNotificationService;

/**
 * Sent synchronously so the link is built from the shop's own domain, not a queue worker's.
 */
class ResetCustomerPasswordNotification extends Notification
{
    public function __construct(public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(CustomerAccount $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CustomerAccount $notifiable): MailMessage
    {
        $shop = app(OrderNotificationService::class)->shop();

        return (new MailMessage)
            ->subject("Reset your {$shop['name']} password")
            ->greeting("Hi {$notifiable->name},")
            ->line("We received a request to reset the password for your {$shop['name']} account.")
            ->action('Choose a new password', route('shop.account.password.reset', [
                'token' => $this->token,
                'email' => $notifiable->email,
            ]))
            ->line('This link expires in '.config('auth.passwords.customer_accounts.expire').' minutes.')
            ->line("If you didn't ask for this, you can ignore this email — your password stays the same.")
            ->salutation("— {$shop['name']}");
    }
}
