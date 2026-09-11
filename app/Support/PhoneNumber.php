<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalise user input to E.164 (e.g. "+972501234567"). Returns null when the number is not plausible.
     */
    public static function normalize(?string $raw, ?string $country = null): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $country = preg_replace('/\D/', '', (string) ($country ?: config('endless.phone.default_country', '972')));
        $plus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if ($plus) {
            $number = $digits;
        } elseif (str_starts_with($digits, '00')) {
            $number = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $number = $country.substr($digits, 1);
        } elseif (str_starts_with($digits, $country) && strlen($digits) >= 11) {
            $number = $digits;
        } else {
            $number = $country.$digits;
        }

        $number = ltrim($number, '0');
        if (strlen($number) < 8 || strlen($number) > 15) {
            return null;
        }

        return '+'.$number;
    }

    /** True when the input looks like a phone number rather than an e-mail. */
    public static function looksLikePhone(?string $input): bool
    {
        $input = trim((string) $input);
        if ($input === '' || str_contains($input, '@')) {
            return false;
        }

        return (bool) preg_match('/^[+\d][\d\s\-().]{6,}$/', $input);
    }

    /** Local Israeli format for SMS gateways: +972501234567 => 0501234567; other countries keep the digits. */
    public static function toGatewayFormat(string $e164): string
    {
        $digits = ltrim($e164, '+');
        if (str_starts_with($digits, '972')) {
            return '0'.substr($digits, 3);
        }

        return $digits;
    }

    /** Human friendly display: +972501234567 => 050-1234567 */
    public static function format(?string $e164): string
    {
        if (! $e164) {
            return '';
        }
        $digits = ltrim($e164, '+');
        if (str_starts_with($digits, '972')) {
            $local = '0'.substr($digits, 3);

            return strlen($local) === 10 ? substr($local, 0, 3).'-'.substr($local, 3) : $local;
        }

        return '+'.$digits;
    }

    /** Split "+972501234567" into ['972', '501234567'] using the configured country list. */
    public static function split(?string $e164): array
    {
        $digits = ltrim((string) $e164, '+');
        $codes = array_keys(config('endless.phone.countries', []));
        usort($codes, fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($codes as $code) {
            if (str_starts_with($digits, (string) $code)) {
                return [(string) $code, substr($digits, strlen((string) $code))];
            }
        }

        return [config('endless.phone.default_country', '972'), $digits];
    }
}
