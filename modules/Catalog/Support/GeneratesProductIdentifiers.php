<?php

namespace Modules\Catalog\Support;

use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

trait GeneratesProductIdentifiers
{
    use GeneratesUniqueSlug;

    protected function uniqueInternalCode(string $name, ?int $ignoreProductId = null): string
    {
        $base = Str::upper(Str::slug($name, '_')) ?: 'ITEM';
        $base = Str::limit($base, 50, '');

        return $this->uniquifyInternalCode($base, $ignoreProductId);
    }

    protected function uniquifyInternalCode(string $code, ?int $ignoreProductId = null): string
    {
        $code = Str::upper(Str::slug($code, '_')) ?: 'ITEM';
        $code = Str::limit($code, 50, '');
        $base = $code;
        $i = 1;

        while ($this->internalCodeExists($code, $ignoreProductId)) {
            $suffix = '_'.$this->paddedSuffix($i);
            $code = Str::limit($base, 60 - strlen($suffix), '').$suffix;
            $i++;
        }

        return $code;
    }

    protected function uniqueSku(string $name, ?string $prefix = null, ?int $ignoreProductId = null, ?int $ignoreVariantId = null): string
    {
        $prefix = trim((string) $prefix);
        $base = Str::upper(Str::slug($name, ''));
        $base = $base !== '' ? $base : 'SKU';
        $base = Str::limit($base, max(8, 70 - strlen($prefix)), '');

        return $this->uniquifySku($prefix.$base, $ignoreProductId, $ignoreVariantId);
    }

    protected function uniquifySku(string $sku, ?int $ignoreProductId = null, ?int $ignoreVariantId = null): string
    {
        $sku = Str::upper(preg_replace('/[^A-Z0-9\-_]/', '', Str::upper($sku)) ?: 'SKU');
        $sku = Str::limit($sku, 80, '');
        $base = $sku;
        $i = 1;

        while ($this->skuExists($sku, $ignoreProductId, $ignoreVariantId)) {
            $suffix = '-'.$this->paddedSuffix($i);
            $sku = Str::limit($base, 80 - strlen($suffix), '').$suffix;
            $i++;
        }

        return $sku;
    }

    protected function uniqueVariantSku(string $productName, int $index, ?string $prefix = null): string
    {
        $prefix = trim((string) $prefix);
        $base = Str::upper(Str::slug($productName, ''));
        $base = $base !== '' ? $base : 'SKU';
        $base = Str::limit($base, 40, '');
        $sku = $prefix.$base.'-V'.$this->paddedSuffix($index);

        return $this->uniquifySku($sku);
    }

    protected function uniqueBarcode(?string $seed = null, ?int $ignoreProductId = null, ?int $ignoreVariantId = null): string
    {
        $base = $seed
            ? Str::upper(preg_replace('/[^A-Z0-9\-]/', '', Str::upper($seed)) ?: '')
            : '';

        if ($base === '') {
            $base = 'BC'.now()->format('ymdHis').Str::upper(Str::random(4));
        }

        return $this->uniquifyBarcode(Str::limit($base, 70, ''), $ignoreProductId, $ignoreVariantId);
    }

    protected function uniquifyBarcode(string $barcode, ?int $ignoreProductId = null, ?int $ignoreVariantId = null): string
    {
        $barcode = Str::upper(preg_replace('/[^A-Z0-9\-]/', '', Str::upper($barcode)) ?: 'BC');
        $barcode = Str::limit($barcode, 80, '');
        $base = $barcode;
        $i = 1;

        while ($this->barcodeExists($barcode, $ignoreProductId, $ignoreVariantId)) {
            $suffix = $this->paddedSuffix($i);
            $barcode = Str::limit($base, 80 - strlen($suffix), '').$suffix;
            $i++;
        }

        return $barcode;
    }

    protected function barcodeFromSku(string $sku, ?int $ignoreProductId = null, ?int $ignoreVariantId = null): string
    {
        if ($sku !== '' && ! $this->barcodeExists($sku, $ignoreProductId, $ignoreVariantId)) {
            return $sku;
        }

        return $this->uniqueBarcode($sku, $ignoreProductId, $ignoreVariantId);
    }

    private function internalCodeExists(string $code, ?int $ignoreProductId = null): bool
    {
        return Product::query()
            ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
            ->where('internal_code', $code)
            ->exists();
    }

    private function skuExists(string $sku, ?int $ignoreProductId = null, ?int $ignoreVariantId = null): bool
    {
        $onProduct = Product::query()
            ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
            ->where('sku', $sku)
            ->exists();

        if ($onProduct) {
            return true;
        }

        return ProductVariant::query()
            ->when($ignoreVariantId, fn ($q) => $q->where('id', '!=', $ignoreVariantId))
            ->where('sku', $sku)
            ->exists();
    }

    private function barcodeExists(string $barcode, ?int $ignoreProductId = null, ?int $ignoreVariantId = null): bool
    {
        $onProduct = Product::query()
            ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
            ->where('barcode', $barcode)
            ->exists();

        if ($onProduct) {
            return true;
        }

        return ProductVariant::query()
            ->when($ignoreVariantId, fn ($q) => $q->where('id', '!=', $ignoreVariantId))
            ->where('barcode', $barcode)
            ->exists();
    }
}
