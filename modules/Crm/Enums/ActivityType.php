<?php

namespace Modules\Crm\Enums;

enum ActivityType: string
{
    case Note = 'note';
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Task = 'task';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Call => 'Call',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::Task => 'Task',
        };
    }
}
