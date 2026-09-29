<?php

namespace App\Jobs;

use App\Core\Contracts\SmsSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120];

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
    ) {}

    public function handle(SmsSender $sms): void
    {
        $sms->send($this->phone, $this->message);
    }
}
