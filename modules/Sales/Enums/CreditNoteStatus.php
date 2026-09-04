<?php

namespace Modules\Sales\Enums;

enum CreditNoteStatus: string
{
    case Issued = 'issued';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Issued',
        };
    }
}
