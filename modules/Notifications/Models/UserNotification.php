<?php

namespace Modules\Notifications\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use BelongsToTenant;

    protected $table = 'user_notifications';

    protected $fillable = ['user_id', 'title', 'body', 'channel', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
