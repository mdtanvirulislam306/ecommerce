<?php

namespace Modules\Ecommerce\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash on delivery',
            self::Online => 'Online payment',
        };
    }
}
