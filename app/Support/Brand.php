<?php

namespace App\Support;

class Brand
{
    public static function name(): string
    {
        return (string) config('brand.name');
    }

    public static function shortName(): string
    {
        return (string) config('brand.short_name');
    }

    public static function tagline(): string
    {
        return (string) config('brand.tagline');
    }

    public static function adminLabel(): string
    {
        return (string) config('brand.admin_label');
    }

    public static function title(?string $page = null): string
    {
        if (filled($page)) {
            return $page.' · '.static::shortName();
        }

        return static::name();
    }
}
