<?php

namespace Modules\Catalog\Enums;

enum AttributeType: string
{
    case Variant = 'variant';
    case Informational = 'informational';

    public function label(): string
    {
        return match ($this) {
            self::Variant => 'Variant',
            self::Informational => 'Informational',
        };
    }
}
