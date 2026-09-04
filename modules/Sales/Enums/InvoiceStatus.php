<?php

namespace Modules\Sales\Enums;

enum InvoiceStatus: string
{
    case Due = 'due';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Due => 'Due',
            self::Partial => 'Partial',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
        };
    }
}
