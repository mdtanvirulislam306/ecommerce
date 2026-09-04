<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Enums\AttributeInputType;
use Modules\Catalog\Enums\AttributeType;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'input_type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
            'input_type' => AttributeInputType::class,
            'is_active' => 'boolean',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('sort_order');
    }

    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function variantAttributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class);
    }
}
