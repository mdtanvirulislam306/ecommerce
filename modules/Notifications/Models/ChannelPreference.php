<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPreference extends Model
{
    protected $table = 'notification_channel_preferences';

    protected $fillable = ['user_id', 'channel', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
