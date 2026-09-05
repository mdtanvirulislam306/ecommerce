<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Models\CatalogSetting;

class CatalogSettingsService extends Service
{
    public function get(): CatalogSetting
    {
        $settings = CatalogSetting::query()->firstOrCreate([], $this->defaults());

        if ($this->needsRepair($settings)) {
            $settings->fill($this->defaults())->save();
            $settings->refresh();
        }

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function forForm(): array
    {
        $settings = $this->get()->load('defaultUnit:id,name,code');

        return [
            'default_product_status' => $settings->default_product_status->value,
            'default_publication_status' => $settings->default_publication_status->value,
            'require_brand_on_create' => $settings->require_brand_on_create,
            'require_primary_category_on_create' => $settings->require_primary_category_on_create,
            'require_unit_on_create' => $settings->require_unit_on_create,
            'auto_submit_for_review_on_create' => $settings->auto_submit_for_review_on_create,
            'sku_prefix' => $settings->sku_prefix,
            'default_unit_id' => $settings->default_unit_id,
            'max_media_per_product' => $settings->max_media_per_product,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): CatalogSetting
    {
        $settings = $this->get();
        $settings->update([
            'default_product_status' => $data['default_product_status'],
            'default_publication_status' => $data['default_publication_status'],
            'require_brand_on_create' => $data['require_brand_on_create'] ?? false,
            'require_primary_category_on_create' => $data['require_primary_category_on_create'] ?? false,
            'require_unit_on_create' => $data['require_unit_on_create'] ?? false,
            'auto_submit_for_review_on_create' => $data['auto_submit_for_review_on_create'] ?? false,
            'sku_prefix' => $data['sku_prefix'] ?? null,
            'default_unit_id' => $data['default_unit_id'] ?: null,
            'max_media_per_product' => $data['max_media_per_product'] ?? 10,
        ]);

        return $settings->fresh();
    }

    public function defaultProductStatus(): ProductStatus
    {
        return $this->get()->default_product_status ?? ProductStatus::Draft;
    }

    public function defaultPublicationStatus(): PublicationStatus
    {
        return $this->get()->default_publication_status ?? PublicationStatus::NotPublished;
    }

    private function needsRepair(CatalogSetting $settings): bool
    {
        $status = $settings->getRawOriginal('default_product_status');
        $publication = $settings->getRawOriginal('default_publication_status');

        return ProductStatus::tryFrom((string) $status) === null
            || PublicationStatus::tryFrom((string) $publication) === null;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'default_product_status' => ProductStatus::Draft,
            'default_publication_status' => PublicationStatus::NotPublished,
            'require_brand_on_create' => false,
            'require_primary_category_on_create' => false,
            'require_unit_on_create' => false,
            'auto_submit_for_review_on_create' => false,
            'max_media_per_product' => 10,
        ];
    }

    public function shouldAutoSubmitForReview(): bool
    {
        return $this->get()->auto_submit_for_review_on_create;
    }

    public function maxMediaPerProduct(): int
    {
        return max(1, (int) $this->get()->max_media_per_product);
    }
}
