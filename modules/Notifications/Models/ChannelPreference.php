<?php

namespace Modules\Notifications\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ChannelPreference extends Model
{
    use BelongsToTenant;

    protected $table = 'notification_channel_preferences';

    protected $fillable = ['user_id', 'channel', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
