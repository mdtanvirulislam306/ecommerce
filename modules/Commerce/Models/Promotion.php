<?php

namespace Modules\Commerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use BelongsToTenant;

    protected $table = 'promotions';

    protected $fillable = [
        'name',
        'type',
        'value',
        'buy_qty',
        'get_qty',
        'starts_at',
        'ends_at',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'buy_qty' => 'integer',
            'get_qty' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
