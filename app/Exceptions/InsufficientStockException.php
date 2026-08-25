<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Collection;

class InsufficientStockException extends Exception
{
    /**
     * @param  Collection<int, array{ingredient: string, needed: float, available: float, short: float, unit: string}>  $shortfalls
     */
    public function __construct(public Collection $shortfalls)
    {
        $message = $shortfalls
            ->map(function (array $row): string {
                return sprintf(
                    '%s needs %s %s (have %s, short %s)',
                    $row['ingredient'],
                    self::qty($row['needed']),
                    $row['unit'],
                    self::qty($row['available']),
                    self::qty($row['short']),
                );
            })
            ->implode('; ');

        parent::__construct('Not enough stock to confirm this order: '.$message);
    }

    private static function qty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
