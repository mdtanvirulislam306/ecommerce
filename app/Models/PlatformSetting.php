<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide key/value settings. Not tenant scoped: one row per key for the whole platform.
 */
class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];
}
