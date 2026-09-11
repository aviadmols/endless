<?php

namespace App\Enums;

enum Religion: string
{
    case Jewish = 'jewish';
    case Christian = 'christian';
    case Muslim = 'muslim';
    case Druze = 'druze';
    case Other = 'other';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Jewish => 'יהדות',
            self::Christian => 'נצרות',
            self::Muslim => 'אסלאם',
            self::Druze => 'דרוזים',
            self::Other => 'אחר',
            self::None => 'ללא סמל',
        };
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
