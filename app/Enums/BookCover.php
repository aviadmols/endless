<?php

namespace App\Enums;

/** The colour the cover is printed on. */
enum BookCover: string
{
    case White = 'white';
    case Cream = 'cream';
    case Sand = 'sand';
    case Sage = 'sage';
    case Ink = 'ink';

    public function label(): string
    {
        return match ($this) {
            self::White => 'לבן',
            self::Cream => 'שמנת',
            self::Sand => 'חול',
            self::Sage => 'מרווה',
            self::Ink => 'פחם',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::White => '#FFFFFF',
            self::Cream => '#F9F8F5',
            self::Sand => '#E7DECD',
            self::Sage => '#D6DBD1',
            self::Ink => '#1D1D20',
        };
    }

    /** Text and rules on the cover, so they stay readable on whatever it is printed on. */
    public function ink(): string
    {
        return $this->isDark() ? '#FFFFFF' : '#1D1D20';
    }

    public function isDark(): bool
    {
        return $this === self::Ink;
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
