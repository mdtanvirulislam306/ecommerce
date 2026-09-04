<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStorefrontSetting extends Model
{
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
