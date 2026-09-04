<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\PublicationStatus;

class CatalogSetting extends Model
{
    protected $fillable = [
        'default_product_status',
        'default_publication_status',
        'require_brand_on_create',
        'require_primary_category_on_create',
        'require_unit_on_create',
        'auto_submit_for_review_on_create',
        'sku_prefix',
        'default_unit_id',
        'max_media_per_product',
    ];

    protected function casts(): array
    {
        return [
            'default_product_status' => ProductStatus::class,
            'default_publication_status' => PublicationStatus::class,
            'require_brand_on_create' => 'boolean',
            'require_primary_category_on_create' => 'boolean',
            'require_unit_on_create' => 'boolean',
            'auto_submit_for_review_on_create' => 'boolean',
        ];
    }

    public function defaultUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }
}
