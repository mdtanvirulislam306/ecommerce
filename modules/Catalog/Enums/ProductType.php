<?php

namespace Modules\Catalog\Enums;

enum ProductType: string
{
    case Simple = 'simple';
    case Variant = 'variant';

    public function label(): string
    {
        return match ($this) {
            self::Simple => 'Simple',
            self::Variant => 'Variant',
        };
    }
}
