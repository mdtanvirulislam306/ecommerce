<?php

namespace Modules\Commerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    use BelongsToTenant;

    protected $table = 'couriers';

    protected $fillable = [
        'name',
        'code',
        'tracking_url_template',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
