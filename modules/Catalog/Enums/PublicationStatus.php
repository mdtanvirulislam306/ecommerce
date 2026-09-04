<?php

namespace Modules\Catalog\Enums;

enum PublicationStatus: string
{
    case NotPublished = 'not_published';
    case Published = 'published';
    case Unpublished = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            self::NotPublished => 'Not Published',
            self::Published => 'Published',
            self::Unpublished => 'Unpublished',
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::NotPublished => $to === self::Published,
            self::Published => $to === self::Unpublished,
            self::Unpublished => $to === self::Published,
        };
    }
}
