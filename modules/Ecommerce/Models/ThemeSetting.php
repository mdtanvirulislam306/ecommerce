<?php

namespace Modules\Ecommerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'code',
        'name',
        'settings',
        'is_installed',
        'is_active',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_installed' => 'boolean',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
        ];
    }
}
