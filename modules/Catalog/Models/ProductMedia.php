<?php

namespace Modules\Catalog\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Enums\MediaType;

class ProductMedia extends Model
{
    use BelongsToTenant;

    protected $table = 'product_media';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'path',
        'type',
        'alt',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'is_primary' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function url(): string
    {
        return '/storage/'.$this->path;
    }
}
