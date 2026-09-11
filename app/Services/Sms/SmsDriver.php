<?php

namespace App\Services\Sms;

interface SmsDriver
{
    public function send(string $toE164, string $message): SmsResult;

    public function name(): string;
}
