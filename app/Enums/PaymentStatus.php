<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case AwaitingPayment = 'awaiting_payment';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::AwaitingPayment => 'Awaiting payment',
            self::PartiallyPaid => 'Deposit paid',
            self::Paid => 'Paid',
            self::Failed => 'Payment failed',
        };
    }
}
