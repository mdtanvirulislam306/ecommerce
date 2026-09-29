<?php

namespace App\Core\Support;

use App\Core\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $message): void
    {
        Log::info('SMS (log driver)', ['to' => $phone, 'message' => $message]);
    }
}
