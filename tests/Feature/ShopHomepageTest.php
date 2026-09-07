<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Services\PlanService;
use Tests\TestCase;

class ShopHomepageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
    }

    public function test_shop_homepage_renders_storefront_sections(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Wireless Headphones',
            'slug' => 'wireless-headphones',
            'sku' => 'WH-001',
            'type' => 'simple',
            'status' => 'active',
            'publication_status' => 'published',
            'primary_category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_storefront_settings')->insert([
            'product_id' => $productId,
            'is_featured' => true,
            'show_on_homepage' => true,
            'storefront_sort_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/Index', false)
                ->has('categories', 1)
                ->where('categories.0.name', 'Electronics')
                ->where('categories.0.slug', 'electronics')
                ->where('category', null)
                ->has('trending', 1)
                ->where('trending.0.name', 'Wireless Headphones')
                ->has('best_selling', 1)
                ->where('search', null)
            );

        $this->get(route('shop.index', ['category' => 'electronics']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/Index', false)
                ->where('category.slug', 'electronics')
                ->has('category_products', 1)
                ->where('category_products.0.name', 'Wireless Headphones')
            );
    }

    public function test_shop_homepage_lists_nested_active_categories(): void
    {
        $menId = DB::table('categories')->insertGetId([
            'name' => 'Men',
            'slug' => 'men',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('categories')->insert([
            [
                'name' => 'Clothing',
                'slug' => 'men-clothing',
                'parent_id' => $menId,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hidden Kids',
                'slug' => 'kids',
                'parent_id' => $menId,
                'is_active' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Women',
                'slug' => 'women',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/Index', false)
                ->has('categories', 2)
                ->where('categories.0.slug', 'men')
                ->where('categories.1.slug', 'women')
            );
    }

    public function test_shop_parent_category_lists_children_and_descendant_products(): void
    {
        $menId = DB::table('categories')->insertGetId([
            'name' => 'Men',
            'slug' => 'men',
            'image_path' => 'categories/men.jpg',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clothingId = DB::table('categories')->insertGetId([
            'name' => 'Clothing',
            'slug' => 'men-clothing',
            'parent_id' => $menId,
            'image_path' => 'categories/clothing.jpg',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tshirtId = DB::table('categories')->insertGetId([
            'name' => 'T-Shirt',
            'slug' => 'men-t-shirt',
            'parent_id' => $clothingId,
            'image_path' => 'categories/tshirt.jpg',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insertGetId([
            'name' => 'Crew Tee',
            'slug' => 'crew-tee',
            'sku' => 'TEE-1',
            'type' => 'simple',
            'status' => 'active',
            'publication_status' => 'published',
            'primary_category_id' => $tshirtId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('shop.index', ['category' => 'men']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/Index', false)
                ->where('category.slug', 'men')
                ->where('category.root_slug', 'men')
                ->where('category.image_url', '/storage/categories/men.jpg')
                ->has('child_categories', 1)
                ->where('child_categories.0.slug', 'men-clothing')
                ->where('child_categories.0.image_url', '/storage/categories/clothing.jpg')
                ->has('category_products', 1)
                ->where('category_products.0.name', 'Crew Tee')
            );

        $this->get(route('shop.index', ['category' => 'men-clothing']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/Index', false)
                ->where('category.slug', 'men-clothing')
                ->where('category.parent_slug', 'men')
                ->where('category.root_slug', 'men')
                ->has('child_categories', 1)
                ->where('child_categories.0.slug', 'men-t-shirt')
                ->has('category_products', 1)
                ->where('category_products.0.name', 'Crew Tee')
            );
    }

    public function test_shop_homepage_search_filters_products(): void
    {
        DB::table('products')->insert([
            [
                'name' => 'Blue Shirt',
                'slug' => 'blue-shirt',
                'sku' => 'SHIRT-1',
                'type' => 'simple',
                'status' => 'active',
                'publication_status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Red Shoes',
                'slug' => 'red-shoes',
                'sku' => 'SHOE-1',
                'type' => 'simple',
                'status' => 'active',
                'publication_status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('shop.index', ['search' => 'Shirt']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/Index', false)
                ->where('search', 'Shirt')
                ->has('search_results', 1)
                ->where('search_results.0.name', 'Blue Shirt')
            );
    }

    public function test_shop_stories_group_by_start_date(): void
    {
        $user = User::factory()->create();
        $dayA = now()->subDay()->startOfDay();
        $dayB = now()->startOfDay();

        DB::table('stories')->insert([
            [
                'user_id' => $user->id,
                'title' => 'Eid Pack A1',
                'type' => 'image',
                'media_path' => 'stories/a1.jpg',
                'action_label' => 'Order Now',
                'is_active' => true,
                'starts_at' => $dayA,
                'expires_at' => null,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Eid Pack A2',
                'type' => 'video',
                'media_path' => 'stories/a2.mp4',
                'action_label' => 'Order Now',
                'is_active' => true,
                'starts_at' => $dayA->copy()->addHours(2),
                'expires_at' => null,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'title' => 'Fresh Drop B',
                'type' => 'image',
                'media_path' => 'stories/b1.jpg',
                'action_label' => 'Shop Now',
                'is_active' => true,
                'starts_at' => $dayB,
                'expires_at' => null,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/Index', false)
                ->has('story_groups', 2)
                ->where('story_groups.0.key', $dayB->toDateString())
                ->where('story_groups.0.count', 1)
                ->where('story_groups.1.key', $dayA->toDateString())
                ->where('story_groups.1.count', 2)
                ->has('story_groups.1.stories', 2)
                ->where('story_groups.1.stories.0.type', 'video')
                ->where('story_groups.1.stories.1.type', 'image')
            );
    }

    public function test_product_quick_endpoint_returns_json(): void
    {
        $productId = DB::table('products')->insertGetId([
            'name' => 'Quick View Tea',
            'slug' => 'quick-view-tea',
            'sku' => 'TEA-1',
            'type' => 'simple',
            'status' => 'active',
            'publication_status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_reviews')->insert([
            'product_id' => $productId,
            'author_name' => 'Ayesha',
            'rating' => 5,
            'title' => 'Great tea',
            'body' => 'Fresh and aromatic.',
            'status' => 'approved',
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson(route('shop.products.quick', 'quick-view-tea'))
            ->assertOk()
            ->assertJsonPath('slug', 'quick-view-tea')
            ->assertJsonPath('has_variants', false)
            ->assertJsonPath('rating_count', 1)
            ->assertJsonPath('reviews.0.author_name', 'Ayesha')
            ->assertJsonPath('reviews.0.rating', 5);
    }
}
