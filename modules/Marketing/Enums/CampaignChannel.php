<?php

namespace Modules\Marketing\Enums;

enum CampaignChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Push = 'push';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::Whatsapp => 'WhatsApp',
            self::Push => 'Push',
        };
    }
}
