<?php

namespace App\Enums;

enum CheckoutPaymentChoice: string
{
    case OnlineDeposit = 'online_deposit';
    case OnlineFull = 'online_full';
    case PayLater = 'pay_later';

    public function label(): string
    {
        return match ($this) {
            self::OnlineDeposit => 'Pay deposit online',
            self::OnlineFull => 'Pay full amount online',
            self::PayLater => 'Pay later',
        };
    }

    public function isOnline(): bool
    {
        return $this === self::OnlineDeposit || $this === self::OnlineFull;
    }
}
