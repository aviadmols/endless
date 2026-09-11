<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDriver;
use App\Services\Sms\SmsResult;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 019SMS (019sms.co.il) JSON API driver.
 *
 * Request:  POST https://019sms.co.il/api
 *           Authorization: Bearer {token}            (token generated in the 019 web panel)
 *           { "sms": { "user": { "username": "..." }, "source": "...", "destinations": { "phone": [ { "_": "05xxxxxxxx" } ] }, "message": "..." } }
 * Legacy accounts without a token may authenticate with { "user": { "username": "...", "password": "..." } }.
 * Response: { "status": 0, "message": "..." }  — status 0 means the message was accepted.
 */
class Sms019Driver implements SmsDriver
{
    public function __construct(
        protected string $username,
        protected ?string $token,
        protected ?string $password,
        protected string $source,
        protected string $endpoint = 'https://019sms.co.il/api',
    ) {}

    public function send(string $toE164, string $message): SmsResult
    {
        $payload = $this->payload($toE164, $message);

        try {
            $request = Http::acceptJson()->asJson()->timeout(20);
            if ($this->token) {
                $request = $request->withToken($this->token);
            }
            $response = $request->post($this->endpoint, $payload);
        } catch (Throwable $e) {
            Log::error('[SMS:019] transport error: '.$e->getMessage());

            return SmsResult::failure('שגיאת תקשורת מול 019SMS: '.$e->getMessage());
        }

        $json = $response->json() ?? [];
        $status = $json['status'] ?? null;

        if ($response->successful() && (string) $status === '0') {
            return SmsResult::success($json['message'] ?? 'sent', $json);
        }

        $error = $json['message'] ?? ('HTTP '.$response->status());
        Log::warning('[SMS:019] send failed', ['status' => $status, 'body' => $response->body()]);

        return SmsResult::failure('019SMS: '.$error, $json ?: ['body' => $response->body()]);
    }

    /** @return array<string,mixed> */
    public function payload(string $toE164, string $message): array
    {
        $user = ['username' => $this->username];
        if (! $this->token && $this->password) {
            $user['password'] = $this->password;
        }

        return [
            'sms' => [
                'user' => $user,
                'source' => $this->source,
                'destinations' => [
                    'phone' => [['_' => PhoneNumber::toGatewayFormat($toE164)]],
                ],
                'message' => $message,
            ],
        ];
    }

    public function name(): string
    {
        return '019sms';
    }
}
