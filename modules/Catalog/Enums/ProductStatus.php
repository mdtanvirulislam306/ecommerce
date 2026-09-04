<?php

namespace Modules\Catalog\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending Review',
            self::Approved => 'Approved',
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Draft => in_array($to, [self::PendingReview, self::Archived], true),
            self::PendingReview => in_array($to, [self::Approved, self::Draft], true),
            self::Approved => in_array($to, [self::Active, self::Draft], true),
            self::Active => $to === self::Archived,
            self::Archived => $to === self::Draft,
        };
    }
}
