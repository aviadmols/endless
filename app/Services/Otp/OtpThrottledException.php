<?php

namespace App\Services\Otp;

use RuntimeException;

class OtpThrottledException extends RuntimeException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct('יותר מדי ניסיונות. נסו שוב בעוד '.max(1, (int) ceil($retryAfter / 60)).' דקות.');
    }
}
