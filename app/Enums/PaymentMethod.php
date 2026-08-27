<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Transfer = 'transfer';
    case Online = 'online';
    case PayLater = 'pay_later';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Transfer => 'Bank transfer',
            self::Online => 'Online payment',
            self::PayLater => 'Pay later',
            self::Other => 'Other',
        };
    }
}
