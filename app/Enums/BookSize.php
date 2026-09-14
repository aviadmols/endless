<?php

namespace App\Enums;

/**
 * The printed sizes we offer. Everything the composer needs to lay a book out —
 * how much text fits on a page, how many photos — hangs off the size, so the
 * preview changes shape and length when you pick a different one.
 */
enum BookSize: string
{
    case Square = 'square_21';
    case Portrait = 'portrait_21_28';
    case Landscape = 'landscape_28_21';

    public function label(): string
    {
        return match ($this) {
            self::Square => 'ריבוע',
            self::Portrait => 'לאורך',
            self::Landscape => 'לרוחב',
        };
    }

    /** Width × height in centimetres. */
    public function dimensions(): array
    {
        return match ($this) {
            self::Square => [21, 21],
            self::Portrait => [21, 28],
            self::Landscape => [28, 21],
        };
    }

    public function dimensionsLabel(): string
    {
        [$w, $h] = $this->dimensions();

        // Isolated as LTR: in an RTL line the × between two numbers takes the
        // paragraph direction and "21×28" would otherwise be drawn as "28×21".
        return "\u{2066}{$w}×{$h}\u{2069} ס״מ";
    }

    public function ratio(): float
    {
        [$w, $h] = $this->dimensions();

        return $w / $h;
    }

    /**
     * Capacity is counted in rendered lines, not characters: the body keeps the
     * line breaks people type, so a short line still costs a whole line. Both
     * numbers were measured against the preview and trimmed for slack.
     */
    public function linesPerPage(): int
    {
        return match ($this) {
            self::Square => 17,
            self::Portrait => 26,
            self::Landscape => 15,
        };
    }

    public function charsPerLine(): int
    {
        return match ($this) {
            self::Square, self::Portrait => 72,
            self::Landscape => 99,
        };
    }

    /**
     * Every preview renders at the same pane width, so a wider book has to draw
     * its type proportionally smaller for the page to stay a true scale model.
     */
    public function typeScale(): float
    {
        return round(21 / $this->dimensions()[0], 4);
    }

    /** Photos on a full-bleed gallery page. */
    public function photosPerPage(): int
    {
        return match ($this) {
            self::Square => 4,
            self::Portrait => 4,
            self::Landscape => 6,
        };
    }

    /** Columns in that grid. */
    public function photoColumns(): int
    {
        return match ($this) {
            self::Square, self::Portrait => 2,
            self::Landscape => 3,
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label().' · '.$case->dimensionsLabel();
        }

        return $out;
    }
}
