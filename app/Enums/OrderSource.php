<?php

namespace App\Enums;

enum OrderSource: string
{
    case Online = 'online';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Manual => 'Manual',
        };
    }
}
