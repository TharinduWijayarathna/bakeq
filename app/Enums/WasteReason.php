<?php

namespace App\Enums;

enum WasteReason: string
{
    case Spoilage = 'spoilage';
    case Mistake = 'mistake';
    case Sample = 'sample';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spoilage => 'Spoilage',
            self::Mistake => 'Mistake',
            self::Sample => 'Sample',
            self::Other => 'Other',
        };
    }
}
