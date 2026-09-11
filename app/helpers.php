<?php

use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /** Read a DB-backed setting (see config/endless.php → settings). */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsRepository::class)->get($key, $default);
    }
}

if (! function_exists('media_url')) {
    /** Public URL for a file stored on the "public" disk (or null). */
    function media_url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}

if (! function_exists('side_image_url')) {
    /** Background image for the split-screen auth / dashboard scene. */
    function side_image_url(): string
    {
        $custom = setting('general.side_image');

        return $custom ? media_url($custom) : asset('images/brand/side-dune.webp');
    }
}

if (! function_exists('landing_bg_url')) {
    /** Landing page background: an uploaded image, or the brand photograph. */
    function landing_bg_url(bool $mobile = false): string
    {
        $custom = setting('landing.image_path');
        if ($custom) {
            return media_url($custom);
        }

        return asset($mobile ? 'images/brand/home-bg-mobile.webp' : 'images/brand/home-bg.webp');
    }
}

if (! function_exists('brand_logo_url')) {
    /** The Endless wordmark, or a logo uploaded in the admin. */
    function brand_logo_url(): string
    {
        $custom = setting('general.logo_path');

        return $custom ? media_url($custom) : asset('images/brand/logo.svg');
    }
}

if (! function_exists('he_date')) {
    /** "12 באוקטובר 2024" */
    function he_date(?\DateTimeInterface $date): string
    {
        return $date ? \Illuminate\Support\Carbon::instance($date)->locale('he')->translatedFormat('j בF Y') : '';
    }
}
