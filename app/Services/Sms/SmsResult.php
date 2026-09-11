<?php

namespace App\Services\Sms;

final class SmsResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message = '',
        public readonly array $raw = [],
    ) {}

    public static function success(string $message = 'sent', array $raw = []): self
    {
        return new self(true, $message, $raw);
    }

    public static function failure(string $message, array $raw = []): self
    {
        return new self(false, $message, $raw);
    }
}
