<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\Services\PlanService;
use Modules\Catalog\Models\Category;
use Tests\TestCase;

class CatalogCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
    }

    public function test_guest_is_redirected_from_category_create(): void
    {
        $this->post(route('products.categories.store'), [
            'name' => 'Men',
        ])->assertRedirectToRoute('login');
    }

    public function test_creates_category_with_media_library_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/men.jpg', 'fake-image');

        $mediaId = DB::table('media_library_items')->insertGetId([
            'name' => 'men.jpg',
            'disk' => 'public',
            'path' => 'media/men.jpg',
            'mime' => 'image/jpeg',
            'size' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('products.categories.store'), [
                'name' => 'Men',
                'is_active' => true,
                'media_library_id' => $mediaId,
            ])
            ->assertRedirectToRoute('products.categories.index');

        $category = Category::query()->where('name', 'Men')->first();

        $this->assertNotNull($category);
        $this->assertSame($mediaId, $category->media_library_id);
        $this->assertNotNull($category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
    }

    public function test_rejects_invalid_media_library_id(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('products.categories.store'), [
                'name' => 'Men',
                'is_active' => true,
                'media_library_id' => 999999,
            ])
            ->assertSessionHasErrors('media_library_id');

        $this->assertDatabaseMissing('categories', ['name' => 'Men']);
    }
}
