<?php

namespace Modules\Ecommerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CmsMenu extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'location',
        'items',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
