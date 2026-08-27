<?php

namespace App\Enums;

enum ShiftStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Missed = 'missed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Missed => 'Missed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Scheduled => 'bg-muted text-muted-foreground',
            self::InProgress => 'bg-primary/10 text-primary',
            self::Completed => 'bg-secondary text-secondary-foreground',
            self::Missed => 'bg-destructive/10 text-destructive',
            self::Cancelled => 'bg-muted text-muted-foreground line-through',
        };
    }
}
