<?php

namespace App\Support;

class Money
{
    public static function format(int $cents): string
    {
        return 'Rs. '.number_format($cents / 100);
    }

    public static function rupeesToCents(int|float|string $rupees): int
    {
        return (int) round(((float) $rupees) * 100);
    }

    public static function centsToRupees(int $cents): int
    {
        return (int) round($cents / 100);
    }
}
