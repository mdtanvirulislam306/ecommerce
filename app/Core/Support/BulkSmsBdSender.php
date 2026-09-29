<?php

namespace App\Core\Support;

use App\Core\Contracts\SmsSender;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BulkSmsBdSender implements SmsSender
{
    private const ACCEPTED = 202;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $senderId,
        private readonly string $endpoint,
    ) {}

    public function send(string $phone, string $message): void
    {
        $response = Http::timeout(15)->asForm()->post($this->endpoint, [
            'api_key' => $this->apiKey,
            'senderid' => $this->senderId,
            'type' => 'text',
            'number' => self::normalizePhone($phone),
            'message' => $message,
        ]);

        if ((int) $response->json('response_code') !== self::ACCEPTED) {
            throw new RuntimeException('BulkSMSBD rejected the message: '.$response->body());
        }
    }

    /**
     * Converts local numbers such as 017XXXXXXXX or +88017XXXXXXXX to the 88017XXXXXXXX format the gateway expects.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '01')) {
            return '88'.$digits;
        }

        return $digits;
    }
}
