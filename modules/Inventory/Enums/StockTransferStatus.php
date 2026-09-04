<?php

namespace Modules\Inventory\Enums;

enum StockTransferStatus: string
{
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
        };
    }
}
