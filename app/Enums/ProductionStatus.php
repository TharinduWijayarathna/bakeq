<?php

namespace App\Enums;

enum ProductionStatus: string
{
    case Planning = 'planning';
    case Baking = 'baking';
    case Decorating = 'decorating';
    case Qc = 'qc';
    case Ready = 'ready';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Baking => 'Baking',
            self::Decorating => 'Decorating',
            self::Qc => 'QC',
            self::Ready => 'Ready',
            self::Delivered => 'Delivered',
        };
    }
}
