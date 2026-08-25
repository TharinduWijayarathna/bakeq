<?php

namespace App\Enums;

enum IngredientUnit: string
{
    case Grams = 'g';
    case Kilograms = 'kg';
    case Millilitres = 'ml';
    case Litres = 'l';
    case Pieces = 'pcs';
    case Packs = 'packs';

    public function label(): string
    {
        return match ($this) {
            self::Grams => 'Grams (g)',
            self::Kilograms => 'Kilograms (kg)',
            self::Millilitres => 'Millilitres (ml)',
            self::Litres => 'Litres (l)',
            self::Pieces => 'Pieces',
            self::Packs => 'Packs',
        };
    }
}
