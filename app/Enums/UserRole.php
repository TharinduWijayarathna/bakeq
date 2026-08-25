<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Baker = 'baker';
    case Decorator = 'decorator';
    case Cashier = 'cashier';
    case Manager = 'manager';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Baker => 'Baker',
            self::Decorator => 'Decorator',
            self::Cashier => 'Cashier',
            self::Manager => 'Manager',
            self::Admin => 'Admin',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * @return list<self>
     */
    public static function staffCases(): array
    {
        return [
            self::Baker,
            self::Decorator,
            self::Cashier,
            self::Manager,
            self::Admin,
        ];
    }
}
