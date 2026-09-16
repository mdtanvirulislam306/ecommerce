<?php

namespace Modules\Support\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SupportCategory extends Model
{
    use BelongsToTenant;

    protected $table = 'support_categories';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
