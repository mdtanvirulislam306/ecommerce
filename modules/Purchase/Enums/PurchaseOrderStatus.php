<?php

namespace Modules\Purchase\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Partial = 'partial';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending Approval',
            self::Approved => 'Approved',
            self::Partial => 'Partially Received',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Draft => in_array($to, [self::Pending, self::Approved, self::Cancelled], true),
            self::Pending => in_array($to, [self::Approved, self::Cancelled], true),
            self::Approved => in_array($to, [self::Partial, self::Received, self::Cancelled], true),
            self::Partial => in_array($to, [self::Received, self::Cancelled], true),
            self::Received, self::Cancelled => false,
        };
    }

    public function canReceive(): bool
    {
        return in_array($this, [self::Approved, self::Partial], true);
    }
}
