<?php

namespace Modules\Settings\Enums;

enum StaffStatus: string
{
    case Active = 'active';
    case Invited = 'invited';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Invited => 'Invitation sent',
            self::Deactivated => 'Deactivated',
        };
    }
}
