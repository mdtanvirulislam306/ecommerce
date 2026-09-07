<?php

namespace Modules\Sales\Enums;

enum SalesOrderStatusField: string
{
    case Order = 'order_status';
    case Delivery = 'delivery_status';
    case Payment = 'payment_status';
    case LineDelivery = 'line_delivery';
    case Invoice = 'invoice';
    case PaymentRecord = 'payment_record';
    case Return = 'return';
    case CreditNote = 'credit_note';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Order',
            self::Delivery => 'Delivery',
            self::Payment => 'Payment',
            self::LineDelivery => 'Line delivery',
            self::Invoice => 'Invoice',
            self::PaymentRecord => 'Payment',
            self::Return => 'Return',
            self::CreditNote => 'Credit note',
        };
    }
}
