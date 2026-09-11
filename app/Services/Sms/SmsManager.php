<?php

namespace App\Services\Sms;

use App\Services\Settings\SettingsRepository;
use App\Services\Sms\Drivers\LogSmsDriver;
use App\Services\Sms\Drivers\Sms019Driver;

class SmsManager
{
    public function __construct(protected SettingsRepository $settings) {}

    public function isConfigured(): bool
    {
        return $this->settings->smsConfigured();
    }

    /** Build the driver selected in the admin settings ("019sms" | "log"). */
    public function driver(?string $name = null): SmsDriver
    {
        $name ??= $this->settings->bool('sms.enabled')
            ? (string) $this->settings->get('sms.driver', '019sms')
            : config('endless.sms.default', 'log');

        return match ($name) {
            '019sms' => new Sms019Driver(
                username: (string) $this->settings->get('sms.019.username'),
                token: $this->settings->get('sms.019.token') ?: null,
                password: $this->settings->get('sms.019.password') ?: null,
                source: (string) ($this->settings->get('sms.019.source') ?: 'Endless'),
                endpoint: (string) config('endless.sms.019sms.endpoint'),
            ),
            default => new LogSmsDriver,
        };
    }

    public function send(string $toE164, string $message): SmsResult
    {
        return $this->driver()->send($toE164, $message);
    }
}
