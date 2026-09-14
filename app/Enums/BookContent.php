<?php

namespace App\Enums;

/** What goes into the printed book. */
enum BookContent: string
{
    case Memories = 'memories';
    case Photos = 'photos';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Memories => 'זיכרונות בלבד',
            self::Photos => 'תמונות בלבד',
            self::Both => 'זיכרונות ותמונות',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Memories => 'כל הזיכרונות המאושרים, עם התמונות שצורפו אליהם',
            self::Photos => 'גלריית התמונות של עמוד ההנצחה',
            self::Both => 'הזיכרונות ואחריהם גלריית התמונות',
        };
    }

    public function includesMemories(): bool
    {
        return $this !== self::Photos;
    }

    public function includesPhotos(): bool
    {
        return $this !== self::Memories;
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
