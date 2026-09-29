<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewOnlineOrderMail extends Mailable implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $order  Snapshot from OrderNotificationService::snapshot()
     * @param  array{name: string, support_email: ?string, url: string}  $shop
     */
    public function __construct(
        public array $order,
        public array $shop,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), $this->shop['name']),
            replyTo: $this->order['customer_email'] ? [new Address($this->order['customer_email'], $this->order['customer_name'])] : [],
            subject: "New order {$this->order['number']} · {$this->order['totals']['grand_total']} from {$this->order['customer_name']}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'ecommerce::mail.order-staff',
            text: 'ecommerce::mail.order-staff-text',
        );
    }
}
