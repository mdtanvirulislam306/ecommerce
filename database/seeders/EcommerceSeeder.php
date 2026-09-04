<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Ecommerce\Enums\ReviewStatus;
use Modules\Ecommerce\Models\ProductReview;
use Modules\Ecommerce\Models\ProductStorefrontSetting;

class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        $products = DB::table('products')->orderBy('id')->limit(3)->get();

        if ($products->isEmpty()) {
            return;
        }

        $sortOrder = 100;

        foreach ($products as $index => $product) {
            ProductStorefrontSetting::query()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'is_featured' => $index < 2,
                    'show_on_homepage' => $index === 0,
                    'storefront_sort_order' => $sortOrder - ($index * 10),
                ],
            );

            if ($index === 0 && in_array($product->status, ['active', 'approved'], true)) {
                DB::table('products')->where('id', $product->id)->update([
                    'publication_status' => PublicationStatus::Published->value,
                    'updated_at' => now(),
                ]);
            }
        }

        $samples = [
            [
                'author_name' => 'Rahim Khan',
                'author_email' => 'rahim@example.com',
                'rating' => 5,
                'title' => 'Excellent quality',
                'body' => 'Product matches the description and arrived quickly.',
                'status' => ReviewStatus::Approved,
            ],
            [
                'author_name' => 'Sadia Ahmed',
                'author_email' => 'sadia@example.com',
                'rating' => 4,
                'title' => 'Good value',
                'body' => 'Happy with the purchase, would recommend.',
                'status' => ReviewStatus::Pending,
            ],
            [
                'author_name' => 'Karim Hossain',
                'author_email' => null,
                'rating' => 2,
                'title' => 'Not as expected',
                'body' => 'Color was different from the photos on the website.',
                'status' => ReviewStatus::Pending,
            ],
            [
                'author_name' => 'Nusrat Jahan',
                'author_email' => 'nusrat@example.com',
                'rating' => 1,
                'title' => 'Poor fit',
                'body' => 'Size chart was inaccurate for this item.',
                'status' => ReviewStatus::Rejected,
            ],
        ];

        foreach ($products as $index => $product) {
            $sample = $samples[$index % count($samples)];

            ProductReview::query()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'author_name' => $sample['author_name'],
                    'title' => $sample['title'],
                ],
                [
                    'author_email' => $sample['author_email'],
                    'rating' => $sample['rating'],
                    'body' => $sample['body'],
                    'status' => $sample['status'],
                    'approved_at' => $sample['status'] === ReviewStatus::Approved ? now() : null,
                ],
            );
        }
    }
}
