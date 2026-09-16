<?php

namespace Modules\Marketing\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Marketing\Enums\DiscountType;

class MarketingCoupon extends Model
{
    use BelongsToTenant;

    protected $table = 'marketing_coupons';

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'usage_limit',
        'used_count',
        'starts_at',
        'ends_at',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'decimal:2',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
