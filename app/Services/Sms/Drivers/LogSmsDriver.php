<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDriver;
use App\Services\Sms\SmsResult;
use Illuminate\Support\Facades\Log;

/** Development driver — writes the SMS to the log instead of sending it. */
class LogSmsDriver implements SmsDriver
{
    public function send(string $toE164, string $message): SmsResult
    {
        Log::info('[SMS:log] to '.$toE164.' :: '.$message);

        return SmsResult::success('logged', ['to' => $toE164, 'message' => $message]);
    }

    public function name(): string
    {
        return 'log';
    }
}
