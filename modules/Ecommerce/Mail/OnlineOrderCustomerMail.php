<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Ecommerce\Services\OrderNotificationService;

class OnlineOrderCustomerMail extends Mailable implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $order  Snapshot from OrderNotificationService::snapshot()
     * @param  array{name: string, support_email: ?string, url: string}  $shop
     */
    public function __construct(
        public array $order,
        public array $shop,
        public string $event,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), $this->shop['name']),
            replyTo: $this->shop['support_email'] ? [new Address($this->shop['support_email'], $this->shop['name'])] : [],
            subject: match ($this->event) {
                OrderNotificationService::EVENT_CONFIRMED => "Your order {$this->order['number']} is confirmed",
                OrderNotificationService::EVENT_CANCELLED => "Your order {$this->order['number']} was cancelled",
                default => "We received your order {$this->order['number']}",
            },
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'ecommerce::mail.order-customer',
            text: 'ecommerce::mail.order-customer-text',
            with: ['headline' => $this->headline(), 'intro' => $this->intro()],
        );
    }

    private function headline(): string
    {
        return match ($this->event) {
            OrderNotificationService::EVENT_CONFIRMED => 'Your order is confirmed',
            OrderNotificationService::EVENT_CANCELLED => 'Your order was cancelled',
            default => 'Thanks for your order!',
        };
    }

    private function intro(): string
    {
        $name = $this->order['customer_first_name'] ?: 'there';

        return match ($this->event) {
            OrderNotificationService::EVENT_CONFIRMED => "Good news, {$name}! We've confirmed your order and are getting it ready for delivery.",
            OrderNotificationService::EVENT_CANCELLED => "Hi {$name}, your order has been cancelled. If you didn't expect this, just reply to this email and we'll help.",
            default => "Hi {$name}, we've received your order and will confirm it shortly. Here's a summary for your records.",
        };
    }
}
