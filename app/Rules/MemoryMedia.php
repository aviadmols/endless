<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * One upload slot that accepts either a photo or a video, each with its own
 * extension list and size limit.
 */
class MemoryMedia implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('לא הצלחנו לקרוא את הקובץ. נסו שוב.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension() ?: (string) $value->guessExtension());
        $imageExtensions = array_map('strtolower', config('endless.uploads.image_mimes', []));
        $videoExtensions = array_map('strtolower', config('endless.uploads.video_mimes', []));
        $kb = $value->getSize() / 1024;

        if (in_array($extension, $imageExtensions, true)) {
            $max = (int) config('endless.uploads.image_max_kb', 8192);
            if ($kb > $max) {
                $fail('התמונה "'.$value->getClientOriginalName().'" גדולה מ-'.round($max / 1024).'MB.');
            }

            return;
        }

        if (in_array($extension, $videoExtensions, true)) {
            $max = (int) config('endless.uploads.video_max_kb', 61440);
            if ($kb > $max) {
                $fail('הסרטון "'.$value->getClientOriginalName().'" גדול מ-'.round($max / 1024).'MB.');
            }

            return;
        }

        $fail('ניתן להעלות תמונות ('.implode(', ', $imageExtensions).') או סרטונים ('.implode(', ', $videoExtensions).') בלבד.');
    }
}
