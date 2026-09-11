<?php

namespace App\Support;

use App\Models\Memorial;
use Illuminate\Support\Str;

class SlugGenerator
{
    /** Basic Hebrew → Latin transliteration so Hebrew names get a readable slug. */
    protected const HEBREW = [
        'א' => 'a', 'ב' => 'b', 'ג' => 'g', 'ד' => 'd', 'ה' => 'h', 'ו' => 'v', 'ז' => 'z', 'ח' => 'ch', 'ט' => 't',
        'י' => 'y', 'כ' => 'k', 'ך' => 'k', 'ל' => 'l', 'מ' => 'm', 'ם' => 'm', 'נ' => 'n', 'ן' => 'n', 'ס' => 's',
        'ע' => 'a', 'פ' => 'p', 'ף' => 'f', 'צ' => 'tz', 'ץ' => 'tz', 'ק' => 'k', 'ר' => 'r', 'ש' => 'sh', 'ת' => 't',
        '״' => '', '׳' => '', 'ְ' => '', 'ֱ' => '', 'ֲ' => '', 'ֳ' => '', 'ִ' => '', 'ֵ' => '', 'ֶ' => '', 'ַ' => '', 'ָ' => '', 'ֹ' => '', 'ֻ' => '', 'ּ' => '', 'ׁ' => '', 'ׂ' => '',
    ];

    public static function transliterate(string $text): string
    {
        return strtr($text, self::HEBREW);
    }

    public static function forMemorial(string $firstName, ?string $lastName = null, ?int $ignoreId = null): string
    {
        $base = Str::slug(self::transliterate(trim($firstName.' '.($lastName ?? ''))));
        if ($base === '' || strlen($base) < 3) {
            $base = 'memorial-'.Str::lower(Str::random(6));
        }
        $base = Str::limit($base, 60, '');

        $slug = $base;
        $i = 2;
        while (Memorial::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
