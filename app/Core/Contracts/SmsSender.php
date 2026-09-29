<?php

namespace App\Core\Contracts;

interface SmsSender
{
    /**
     * Deliver a text message, throwing when the provider rejects it so queued sends can retry.
     */
    public function send(string $phone, string $message): void;
}
