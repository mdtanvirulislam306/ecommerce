<?php

namespace Modules\Catalog\Services;

use App\Core\Support\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\ProductType;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductFamily;
use Modules\Catalog\Models\Unit;
use Modules\Catalog\Support\GeneratesUniqueSlug;

class ProductImportExportService extends Service
{
    use GeneratesUniqueSlug;

    public function __construct(
        private readonly CatalogSettingsService $catalogSettings,
    ) {}

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            'sku',
            'name',
            'type',
            'status',
            'publication_status',
            'description',
            'internal_code',
            'barcode',
            'brand',
            'primary_category',
            'unit',
            'product_family',
            'sort_order',
            'meta_title',
            'meta_description',
        ];
    }

    /**
     * @return \Generator<int, list<string|null>>
     */
    public function exportRows(?ProductStatus $status = null): \Generator
    {
        $query = Product::query()
            ->with([
                'brand:id,name',
                'primaryCategory:id,name,slug',
                'unit:id,code',
                'productFamily:id,name,slug',
            ])
            ->orderBy('name');

        if ($status) {
            $query->status($status);
        } else {
            $query->where('status', '!=', ProductStatus::Archived->value);
        }

        foreach ($query->cursor() as $product) {
            yield [
                $product->sku,
                $product->name,
                $product->type->value,
                $product->status->value,
                $product->publication_status->value,
                $product->description,
                $product->internal_code,
                $product->barcode,
                $product->brand?->name,
                $product->primaryCategory?->slug ?? $product->primaryCategory?->name,
                $product->unit?->code,
                $product->productFamily?->slug ?? $product->productFamily?->name,
                (string) $product->sort_order,
                $product->meta_title,
                $product->meta_description,
            ];
        }
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function import(UploadedFile $file, bool $updateExisting = true): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['row' => 0, 'message' => 'Could not read the uploaded file.']],
            ];
        }

        $headerRow = fgetcsv($handle);

        if ($headerRow === false) {
            fclose($handle);

            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['row' => 1, 'message' => 'CSV file is empty.']],
            ];
        }

        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), $headerRow);
        $required = ['sku', 'name'];
        $missing = array_diff($required, $headers);

        if ($missing !== []) {
            fclose($handle);

            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['row' => 1, 'message' => 'Missing required columns: '.implode(', ', $missing).'.']],
            ];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            /** @var array<string, string|null> $data */
            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
                if ($data[$header] === '') {
                    $data[$header] = null;
                }
            }

            try {
                $result = DB::transaction(fn () => $this->importRow($data, $updateExisting));

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $exception) {
                $errors[] = ['row' => $rowNumber, 'message' => $exception->getMessage()];
            }
        }

        fclose($handle);

        return compact('created', 'updated', 'skipped', 'errors');
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function importRow(array $data, bool $updateExisting): string
    {
        $sku = $data['sku'] ?? null;
        $name = $data['name'] ?? null;

        if (! $sku || ! $name) {
            throw new \InvalidArgumentException('SKU and name are required.');
        }

        $type = ProductType::tryFrom($data['type'] ?? ProductType::Simple->value) ?? ProductType::Simple;

        if ($type === ProductType::Variant) {
            throw new \InvalidArgumentException('Variant products cannot be imported via CSV. Use simple type or create variants in the admin UI.');
        }

        $existing = Product::query()->where('sku', $sku)->first();

        if ($existing && ! $updateExisting) {
            return 'skipped';
        }

        if ($existing && $existing->isVariant()) {
            throw new \InvalidArgumentException("SKU {$sku} belongs to a variant product and cannot be updated via CSV.");
        }

        $payload = [
            'type' => ProductType::Simple->value,
            'name' => $name,
            'sku' => $sku,
            'description' => $data['description'] ?? null,
            'internal_code' => $data['internal_code'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'status' => $this->resolveEnum($data['status'] ?? null, ProductStatus::class) ?? $this->catalogSettings->defaultProductStatus()->value,
            'publication_status' => $this->resolveEnum($data['publication_status'] ?? null, PublicationStatus::class) ?? $this->catalogSettings->defaultPublicationStatus()->value,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'brand_id' => $this->resolveBrandId($data['brand'] ?? null),
            'primary_category_id' => $this->resolveCategoryId($data['primary_category'] ?? null),
            'unit_id' => $this->resolveUnitId($data['unit'] ?? null),
            'product_family_id' => $this->resolveFamilyId($data['product_family'] ?? null),
        ];

        if ($existing) {
            $payload['slug'] = $existing->slug;
            $existing->update($payload);

            return 'updated';
        }

        $payload['slug'] = $this->uniqueSlug($name, Product::class);
        Product::query()->create($payload);

        return 'created';
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     */
    private function resolveEnum(?string $value, string $enumClass): ?string
    {
        if ($value === null) {
            return null;
        }

        $case = $enumClass::tryFrom($value);

        return $case?->value;
    }

    private function resolveBrandId(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        $brand = Brand::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($value)])
            ->first();

        if ($brand === null) {
            throw new \InvalidArgumentException("Brand not found: {$value}");
        }

        return $brand->id;
    }

    private function resolveCategoryId(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        $category = Category::query()
            ->where(function ($query) use ($value) {
                $query->where('slug', $value)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($value)]);
            })
            ->first();

        if ($category === null) {
            throw new \InvalidArgumentException("Category not found: {$value}");
        }

        return $category->id;
    }

    private function resolveUnitId(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        $unit = Unit::query()->where('code', $value)->first();

        if ($unit === null) {
            throw new \InvalidArgumentException("Unit not found: {$value}");
        }

        return $unit->id;
    }

    private function resolveFamilyId(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        $family = ProductFamily::query()
            ->where(function ($query) use ($value) {
                $query->where('slug', $value)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($value)]);
            })
            ->first();

        if ($family === null) {
            throw new \InvalidArgumentException("Product family not found: {$value}");
        }

        return $family->id;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
