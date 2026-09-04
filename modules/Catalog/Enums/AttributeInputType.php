<?php

namespace Modules\Catalog\Enums;

enum AttributeInputType: string
{
    case Select = 'select';
    case Text = 'text';
    case Number = 'number';

    public function label(): string
    {
        return match ($this) {
            self::Select => 'Select',
            self::Text => 'Text',
            self::Number => 'Number',
        };
    }
}
