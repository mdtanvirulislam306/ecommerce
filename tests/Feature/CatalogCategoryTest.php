<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_creates_category_with_image(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('men.jpg');

        $this->actingAs(User::factory()->create())
            ->post(route('products.categories.store'), [
                'name' => 'Men',
                'is_active' => true,
                'image' => $image,
            ])
            ->assertRedirectToRoute('products.categories.index');

        $category = Category::query()->where('name', 'Men')->first();

        $this->assertNotNull($category);
        $this->assertNotNull($category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
    }

    public function test_rejects_non_image_category_upload(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post(route('products.categories.store'), [
                'name' => 'Men',
                'is_active' => true,
                'image' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('categories', ['name' => 'Men']);
    }
}
