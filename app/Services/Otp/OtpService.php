<?php

namespace App\Services\Otp;

use App\Enums\OtpChannel;
use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    /** Plain code of the last issued OTP — used by tests and by the local "dev code" banner. */
    public ?string $lastPlainCode = null;

    public function __construct(
        protected SmsManager $sms,
        protected SettingsRepository $settings,
    ) {}

    /** Decide which channel to use for a user / registration payload. */
    public function channelFor(?string $email, ?string $phone): OtpChannel
    {
        $preferred = (string) $this->settings->get('otp.preferred_channel', 'auto');
        $smsReady = $phone && $this->sms->isConfigured();
        $mailReady = $email && ($this->settings->mailConfigured() || app()->environment(['local', 'testing']));

        if ($preferred === 'sms' && $smsReady) {
            return OtpChannel::Sms;
        }
        if ($preferred === 'email' && $mailReady) {
            return OtpChannel::Email;
        }
        if ($smsReady) {
            return OtpChannel::Sms;
        }
        if ($mailReady) {
            return OtpChannel::Email;
        }

        // Nothing is configured yet — fall back to whatever identifier exists (log drivers will record it).
        return $phone ? OtpChannel::Sms : OtpChannel::Email;
    }

    public function channelsAvailable(?string $email, ?string $phone): array
    {
        $out = [];
        if ($phone) {
            $out[] = OtpChannel::Sms;
        }
        if ($email) {
            $out[] = OtpChannel::Email;
        }

        return $out;
    }

    /**
     * Create + deliver a one-time code.
     *
     * @throws OtpThrottledException|OtpDeliveryException
     */
    public function issue(string $identifier, OtpChannel $channel, string $purpose = 'login', ?User $user = null): OtpCode
    {
        $identifier = $this->normalizeIdentifier($identifier);
        $this->assertNotThrottled($identifier);

        $code = $this->generateCode();
        $this->lastPlainCode = $code;

        OtpCode::query()
            ->where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $otp = OtpCode::create([
            'user_id' => $user?->id,
            'identifier' => $identifier,
            'channel' => $channel->value,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('endless.otp.ttl_minutes', 10)),
            'ip' => request()?->ip(),
        ]);

        $this->deliver($identifier, $channel, $code, $purpose);

        RateLimiter::hit($this->identifierKey($identifier), 600);
        RateLimiter::hit($this->ipKey(), 600);

        return $otp;
    }

    /** Returns the consumed OtpCode on success, null when the code is wrong/expired. */
    public function verify(string $identifier, string $code, string $purpose = 'login'): ?OtpCode
    {
        $identifier = $this->normalizeIdentifier($identifier);
        $code = preg_replace('/\D/', '', $code) ?? '';

        $otp = OtpCode::query()
            ->where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp) {
            return null;
        }

        $otp->increment('attempts');
        if ($otp->attempts > (int) config('endless.otp.max_attempts', 5)) {
            $otp->forceFill(['consumed_at' => now()])->save();

            return null;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            return null;
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return $otp;
    }

    public function secondsUntilResend(string $identifier, string $purpose = 'login'): int
    {
        $identifier = $this->normalizeIdentifier($identifier);
        $last = OtpCode::query()->where('identifier', $identifier)->where('purpose', $purpose)->latest('id')->first();
        if (! $last) {
            return 0;
        }
        $wait = (int) config('endless.otp.resend_seconds', 45);
        $elapsed = $last->created_at->diffInSeconds(now());

        return max(0, $wait - (int) $elapsed);
    }

    /* ----------------------------------------------------------- internals */

    protected function deliver(string $identifier, OtpChannel $channel, string $code, string $purpose): void
    {
        $brand = config('endless.brand.name', 'Endless');

        if ($channel === OtpChannel::Sms) {
            $message = "קוד הכניסה שלך ל-{$brand}: {$code}\nהקוד תקף ל-".config('endless.otp.ttl_minutes', 10).' דקות.';
            $result = $this->sms->send($identifier, $message);
            if (! $result->ok) {
                throw new OtpDeliveryException('שליחת ה-SMS נכשלה: '.$result->message);
            }

            return;
        }

        Mail::to($identifier)->send(new OtpCodeMail($code, $purpose));
    }

    protected function generateCode(): string
    {
        $dev = config('endless.otp.dev_code');
        if ($dev && app()->environment(['local', 'testing'])) {
            return (string) $dev;
        }
        $length = (int) config('endless.otp.length', 6);

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    protected function assertNotThrottled(string $identifier): void
    {
        $perId = (int) config('endless.otp.per_identifier_per_10m', 4);
        $perIp = (int) config('endless.otp.per_ip_per_10m', 12);

        if (RateLimiter::tooManyAttempts($this->identifierKey($identifier), $perId)) {
            throw new OtpThrottledException(RateLimiter::availableIn($this->identifierKey($identifier)));
        }
        if (RateLimiter::tooManyAttempts($this->ipKey(), $perIp)) {
            throw new OtpThrottledException(RateLimiter::availableIn($this->ipKey()));
        }
    }

    protected function identifierKey(string $identifier): string
    {
        return 'otp:id:'.sha1($identifier);
    }

    protected function ipKey(): string
    {
        return 'otp:ip:'.(request()?->ip() ?? 'cli');
    }

    public function normalizeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        return str_contains($identifier, '@') ? mb_strtolower($identifier) : $identifier;
    }
}
