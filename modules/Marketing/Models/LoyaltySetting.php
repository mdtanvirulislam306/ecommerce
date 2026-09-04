<?php

namespace Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltySetting extends Model
{
    protected $table = 'marketing_loyalty_settings';

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
