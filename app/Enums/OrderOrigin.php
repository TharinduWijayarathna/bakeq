<?php

namespace App\Enums;

enum OrderOrigin: string
{
    case Catalog = 'catalog';
    case AiDesigner = 'ai_designer';
    case AiRedesign = 'ai_redesign';

    public function label(): string
    {
        return match ($this) {
            self::Catalog => 'Catalog',
            self::AiDesigner => 'AI Designer',
            self::AiRedesign => 'AI Redesign',
        };
    }

    public function isAiDesigned(): bool
    {
        return $this !== self::Catalog;
    }
}
