<?php

namespace Modules\Pos\Enums;

enum PosPaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Nagad = 'nagad';
    case Bkash = 'bkash';
    case Bank = 'bank';
    case Mobile = 'mobile';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Nagad => 'Nagad',
            self::Bkash => 'bKash',
            self::Bank => 'Bank',
            self::Mobile => 'Mobile',
        };
    }

    public function requiresCashTender(): bool
    {
        return $this === self::Cash;
    }

    /**
     * @return list<self>
     */
    public static function terminalOptions(): array
    {
        return [
            self::Cash,
            self::Card,
            self::Nagad,
            self::Bkash,
            self::Bank,
        ];
    }
}
