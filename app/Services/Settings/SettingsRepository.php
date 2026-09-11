<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * DB-backed application settings (SMTP, SMS, landing page content ...).
 * Values flagged as secret in config('endless.settings') are stored encrypted.
 */
class SettingsRepository
{
    public const CACHE_KEY = 'endless.settings.v1';

    /** @var array<string,string>|null */
    protected ?array $loaded = null;

    /** @return array<string,array{default:string,secret:bool}> */
    public function schema(): array
    {
        return config('endless.settings', []);
    }

    public function isSecret(string $key): bool
    {
        return (bool) ($this->schema()[$key]['secret'] ?? false);
    }

    public function default(string $key): mixed
    {
        return $this->schema()[$key]['default'] ?? null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->load();
        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default ?? $this->default($key);
    }

    public function bool(string $key): bool
    {
        return in_array((string) $this->get($key), ['1', 'true', 'on', 'yes'], true);
    }

    /** All keys from the schema merged with stored values. */
    public function all(): array
    {
        $out = [];
        foreach ($this->schema() as $key => $meta) {
            $out[$key] = $this->get($key);
        }

        return $out;
    }

    /** Settings for a group prefix, e.g. "mail" => ['host' => ..., 'port' => ...]. */
    public function group(string $prefix): array
    {
        $out = [];
        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, $prefix.'.')) {
                $out[substr($key, strlen($prefix) + 1)] = $value;
            }
        }

        return $out;
    }

    public function set(string $key, mixed $value): void
    {
        $value = $value === null ? null : (string) $value;
        $secret = $this->isSecret($key);

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => ($secret && $value !== null && $value !== '') ? Crypt::encryptString($value) : $value,
                'is_encrypted' => $secret && $value !== null && $value !== '',
                'updated_at' => now(),
            ]
        );

        $this->flush();
    }

    /** @param array<string,mixed> $values */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }

    /* ----------------------------------------------------------- readiness */

    public function mailConfigured(): bool
    {
        return $this->bool('mail.enabled') && $this->get('mail.host') && $this->get('mail.from_address');
    }

    public function smsConfigured(): bool
    {
        if (! $this->bool('sms.enabled')) {
            return false;
        }
        if ($this->get('sms.driver') === 'log') {
            return true;
        }

        return (bool) ($this->get('sms.019.username') && ($this->get('sms.019.token') || $this->get('sms.019.password')));
    }

    /* ----------------------------------------------------------- internals */

    /** @return array<string,string|null> */
    protected function load(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = Cache::rememberForever(self::CACHE_KEY, function () {
                return Setting::query()->get(['key', 'value', 'is_encrypted'])
                    ->map(fn (Setting $s) => ['key' => $s->key, 'value' => $s->value, 'is_encrypted' => $s->is_encrypted])
                    ->all();
            });
        } catch (Throwable) {
            // Table not migrated yet (e.g. during `migrate`) — behave as if nothing is stored.
            return $this->loaded = [];
        }

        $out = [];
        foreach ($rows as $row) {
            $value = $row['value'];
            if ($row['is_encrypted'] && $value !== null && $value !== '') {
                try {
                    $value = Crypt::decryptString($value);
                } catch (DecryptException) {
                    $value = '';
                }
            }
            $out[$row['key']] = $value;
        }

        return $this->loaded = $out;
    }
}
