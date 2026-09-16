<?php

namespace Modules\Ecommerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProductStorefrontSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'product_id',
        'is_featured',
        'show_on_homepage',
        'storefront_sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'show_on_homepage' => 'boolean',
        ];
    }
}
