<?php

namespace Modules\Ecommerce\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Pay on delivery',
            self::Pending => 'Awaiting payment',
            self::Paid => 'Paid',
            self::Failed => 'Payment failed',
        };
    }
}
