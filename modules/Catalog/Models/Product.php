<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\ProductType;
use Modules\Catalog\Enums\PublicationStatus;

class Product extends Model
{
    protected $fillable = [
        'product_family_id',
        'brand_id',
        'primary_category_id',
        'unit_id',
        'type',
        'name',
        'slug',
        'description',
        'internal_code',
        'sku',
        'barcode',
        'status',
        'publication_status',
        'meta_title',
        'meta_description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'publication_status' => PublicationStatus::class,
        ];
    }

    public function productFamily(): BelongsTo
    {
        return $this->belongsTo(ProductFamily::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')->withTimestamps();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_product')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function isSimple(): bool
    {
        return $this->type === ProductType::Simple;
    }

    public function isVariant(): bool
    {
        return $this->type === ProductType::Variant;
    }

    public function scopeStatus(Builder $query, ProductStatus|string $status): Builder
    {
        $value = $status instanceof ProductStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopePublicationStatus(Builder $query, PublicationStatus|string $status): Builder
    {
        $value = $status instanceof PublicationStatus ? $status->value : $status;

        return $query->where('publication_status', $value);
    }

    public function scopeType(Builder $query, ProductType|string $type): Builder
    {
        $value = $type instanceof ProductType ? $type->value : $type;

        return $query->where('type', $value);
    }
}
