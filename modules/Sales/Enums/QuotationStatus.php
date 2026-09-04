<?php

namespace Modules\Sales\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Expired => 'Expired',
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Draft => in_array($to, [self::Sent, self::Accepted, self::Expired], true),
            self::Sent => in_array($to, [self::Accepted, self::Expired], true),
            self::Accepted, self::Expired => false,
        };
    }

    public function canConvertToOrder(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Accepted], true);
    }
}
