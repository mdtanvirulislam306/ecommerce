<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\Enums\MediaType;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;

class DemoStorefrontImagesSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private array $productPhotoKeys = [
        'banana' => 'banana.jpg',
        'tomato' => 'tomato.jpg',
        'potato chips' => 'chips.jpg',
        'potato' => 'potato.jpg',
        'chicken' => 'chicken.jpg',
        'fish' => 'fish.jpg',
        'rui' => 'fish.jpg',
        'tuna' => 'fish.jpg',
        'beef' => 'beef.jpg',
        'soybean oil' => 'oil.jpg',
        'oil 5l' => 'oil.jpg',
        'rice' => 'rice.jpg',
        'ketchup' => 'ketchup.jpg',
        'pickle' => 'ketchup.jpg',
        'soy sauce' => 'ketchup.jpg',
        'milk' => 'milk.jpg',
        'egg' => 'eggs.jpg',
        'cheese' => 'cheese.jpg',
        'oat' => 'oats.jpg',
        'corn flakes' => 'oats.jpg',
        'honey' => 'honey.jpg',
        'chocolate' => 'chocolate.jpg',
        'candy' => 'chocolate.jpg',
        'chips' => 'chips.jpg',
        'chanachur' => 'chips.jpg',
        'biscuit' => 'bread.jpg',
        'water' => 'water.jpg',
        'juice' => 'juice.jpg',
        'cola' => 'cola.jpg',
        'tea' => 'tea.jpg',
        'flour' => 'bread.jpg',
        'paratha' => 'bread.jpg',
        'dal' => 'lentils.jpg',
        'lentil' => 'lentils.jpg',
        'chickpea' => 'lentils.jpg',
        't-shirt' => 'tshirt.jpg',
        'polo' => 'tshirt.jpg',
        'hoodie' => 'tshirt.jpg',
    ];

    public function run(): void
    {
        $categories = $this->syncCategoryImages();
        $products = $this->syncProductImages();

        $this->command?->info("Storefront photos applied: {$categories} categories, {$products} products.");
    }

    private function syncCategoryImages(): int
    {
        $updated = 0;

        foreach (Category::query()->orderBy('id')->get() as $category) {
            $contents = $this->fixtureContents('categories/'.$category->slug.'.jpg');

            if ($contents === null) {
                continue;
            }

            $path = 'categories/'.$category->slug.'.jpg';
            Storage::disk('public')->put($path, $contents);

            $previous = $category->image_path;
            $category->update(['image_path' => $path]);

            if (is_string($previous) && $previous !== $path) {
                Storage::disk('public')->delete($previous);
            }

            $updated++;
        }

        return $updated;
    }

    private function syncProductImages(): int
    {
        $updated = 0;

        $products = Product::query()
            ->where('status', '!=', 'archived')
            ->where(function ($query): void {
                $query->where('sku', 'like', 'DEMO-%')
                    ->orWhere('sku', 'like', 'BB-%');
            })
            ->orderBy('id')
            ->get();

        foreach ($products as $product) {
            $contents = $this->productImageContents($product);

            if ($contents === null) {
                continue;
            }

            $path = 'products/'.Str::slug($product->sku).'.jpg';
            Storage::disk('public')->put($path, $contents);

            foreach ($product->media as $media) {
                if ($media->path !== $path) {
                    Storage::disk('public')->delete($media->path);
                }
                $media->delete();
            }

            ProductMedia::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'type' => MediaType::Image,
                'alt' => $product->name,
                'is_primary' => true,
                'sort_order' => 1,
            ]);

            $updated++;
        }

        return $updated;
    }

    private function productImageContents(Product $product): ?string
    {
        $haystack = Str::lower($product->name.' '.$product->sku);

        foreach ($this->productPhotoKeys as $needle => $file) {
            if (str_contains($haystack, $needle)) {
                $contents = $this->fixtureContents('products/'.$file);

                if ($contents !== null) {
                    return $contents;
                }
            }
        }

        $categorySlug = $product->primaryCategory?->slug;

        if (! is_string($categorySlug) || $categorySlug === '') {
            return null;
        }

        return $this->fixtureContents('categories/'.$categorySlug.'.jpg');
    }

    private function fixtureContents(string $relative): ?string
    {
        $path = database_path('seeders/fixtures/'.$relative);

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false || $contents === '') {
            return null;
        }

        return $contents;
    }
}
