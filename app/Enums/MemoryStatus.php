<?php

namespace App\Enums;

enum MemoryStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'ממתין לאישור',
            self::Approved => 'מאושר',
            self::Rejected => 'נדחה',
        };
    }
}
