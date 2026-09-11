<?php

namespace App\Services\Mail;

use App\Services\Settings\SettingsRepository;

/**
 * Applies the SMTP settings stored in the database (admin → settings → mail) to the runtime mail config.
 * When SMTP is not enabled the mailer configured in .env (log / array) stays in place.
 */
class DynamicMailConfigurator
{
    public function __construct(protected SettingsRepository $settings) {}

    public function apply(): void
    {
        if (! $this->settings->mailConfigured()) {
            $from = $this->settings->get('mail.from_address');
            if ($from) {
                config(['mail.from.address' => $from, 'mail.from.name' => $this->settings->get('mail.from_name') ?: config('app.name')]);
            }

            return;
        }

        $encryption = strtolower((string) $this->settings->get('mail.encryption', 'tls'));

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $this->settings->get('mail.host'),
            'mail.mailers.smtp.port' => (int) $this->settings->get('mail.port', 587),
            'mail.mailers.smtp.username' => $this->settings->get('mail.username') ?: null,
            'mail.mailers.smtp.password' => $this->settings->get('mail.password') ?: null,
            'mail.mailers.smtp.timeout' => 20,
            'mail.from.address' => $this->settings->get('mail.from_address'),
            'mail.from.name' => $this->settings->get('mail.from_name') ?: config('app.name'),
        ]);

        // Drop any mailer instance that was resolved with the old config.
        if (app()->resolved('mail.manager')) {
            app('mail.manager')->forgetMailers();
        }
    }
}
