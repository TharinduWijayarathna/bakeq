<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Baking = 'baking';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Baking => 'Baking',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'oklch(0.72 0.14 70)',
            self::Confirmed => 'oklch(0.68 0.13 200)',
            self::Baking => 'oklch(0.62 0.24 348)',
            self::Delivered => 'oklch(0.62 0.14 145)',
            self::Cancelled => 'oklch(0.58 0.22 27)',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-muted text-muted-foreground',
            self::Confirmed => 'bg-accent/15 text-accent',
            self::Baking => 'bg-primary/10 text-primary',
            self::Delivered => 'bg-secondary text-secondary-foreground',
            self::Cancelled => 'bg-destructive/10 text-destructive',
        };
    }
}
