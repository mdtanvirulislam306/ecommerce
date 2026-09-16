<?php

namespace Modules\Commerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LoyaltyProgram extends Model
{
    use BelongsToTenant;

    protected $table = 'loyalty_programs';

    protected $fillable = [
        'name',
        'points_per_currency',
        'redemption_rate',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'points_per_currency' => 'decimal:4',
            'redemption_rate' => 'decimal:4',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }
}
