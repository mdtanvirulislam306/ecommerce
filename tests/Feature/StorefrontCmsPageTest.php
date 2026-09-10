<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Services\PlanService;
use Modules\Ecommerce\Models\CmsFormSubmission;
use Modules\Ecommerce\Models\CmsPage;
use Tests\TestCase;

class StorefrontCmsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
    }

    public function test_unpublished_slug_returns_404_for_guests(): void
    {
        CmsPage::query()->create([
            'title' => 'Draft page',
            'slug' => 'draft-page',
            'is_published' => false,
            'blocks' => $this->headingDocument(),
        ]);

        $this->get(route('shop.pages.show', 'draft-page'))
            ->assertNotFound();
    }

    public function test_published_page_renders_cms_page_component(): void
    {
        CmsPage::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'is_published' => true,
            'seo_title' => 'About the shop',
            'blocks' => $this->headingDocument('Our story'),
        ]);

        $this->get(route('shop.pages.show', 'about'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/CmsPage', false)
                ->where('page.title', 'About')
                ->where('page.seo_title', 'About the shop')
                ->where('page.blocks.sections.0.columns.0.widgets.0.settings.text', 'Our story')
            );
    }

    public function test_product_grid_widget_hydrates_catalog_products(): void
    {
        $this->seedPublishedProduct('Wireless Headphones');

        CmsPage::query()->create([
            'title' => 'Shop picks',
            'slug' => 'picks',
            'is_published' => true,
            'blocks' => $this->widgetDocument('product_grid', [
                'heading' => 'Featured',
                'source' => 'featured',
                'limit' => 8,
                'columns' => 4,
            ]),
        ]);

        $this->get(route('shop.pages.show', 'picks'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/CmsPage', false)
                ->has('page.blocks.sections.0.columns.0.widgets.0.data.products', 1)
                ->where('page.blocks.sections.0.columns.0.widgets.0.data.products.0.name', 'Wireless Headphones')
            );
    }

    public function test_contact_form_post_persists_a_submission(): void
    {
        CmsPage::query()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'is_published' => true,
            'blocks' => $this->widgetDocument('contact_form', [
                'heading' => 'Write us',
                'button_label' => 'Send',
            ]),
        ]);

        $response = $this->from(route('shop.pages.show', 'contact'))->post(route('shop.pages.forms.store', 'contact'), [
            'type' => 'contact',
            'widget_id' => 'widget-1',
            'name' => 'Naima',
            'email' => 'naima@example.com',
            'message' => 'Do you ship nationwide?',
        ]);

        $response->assertRedirect(route('shop.pages.show', 'contact'));
        $response->assertSessionHas('success', 'Thanks, we received your message.');

        $submission = CmsFormSubmission::query()->first();
        $this->assertNotNull($submission);
        $this->assertSame('contact', $submission->type);
        $this->assertSame('widget-1', $submission->widget_id);
        $this->assertSame('Naima', $submission->payload['name']);
        $this->assertSame('naima@example.com', $submission->payload['email']);
        $this->assertSame('Do you ship nationwide?', $submission->payload['message']);
    }

    public function test_newsletter_post_persists_a_subscription(): void
    {
        CmsPage::query()->create([
            'title' => 'Updates',
            'slug' => 'updates',
            'is_published' => true,
            'blocks' => $this->widgetDocument('newsletter', [
                'heading' => 'Join',
                'placeholder' => 'Email',
                'button_label' => 'Subscribe',
            ]),
        ]);

        $response = $this->from(route('shop.pages.show', 'updates'))->post(route('shop.pages.forms.store', 'updates'), [
            'type' => 'newsletter',
            'widget_id' => 'widget-1',
            'email' => 'join@example.com',
        ]);

        $response->assertRedirect(route('shop.pages.show', 'updates'));
        $response->assertSessionHas('success', 'Thanks for subscribing.');
        $this->assertDatabaseHas('cms_form_submissions', [
            'type' => 'newsletter',
            'widget_id' => 'widget-1',
        ]);
    }

    public function test_html_widget_strips_script_tags_on_storefront(): void
    {
        CmsPage::query()->create([
            'title' => 'HTML',
            'slug' => 'html-page',
            'is_published' => true,
            'blocks' => $this->widgetDocument('html', [
                'content' => '<p>Safe</p><script>alert(1)</script>',
            ]),
        ]);

        $this->get(route('shop.pages.show', 'html-page'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/CmsPage', false)
                ->where('page.blocks.sections.0.columns.0.widgets.0.data.html', '<p>Safe</p>')
            );
    }

    public function test_guest_preview_redirects_to_login(): void
    {
        CmsPage::query()->create([
            'title' => 'Draft',
            'slug' => 'secret-draft',
            'is_published' => false,
            'blocks' => $this->headingDocument(),
        ]);

        $this->get(route('shop.pages.preview', 'secret-draft'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_preview_renders_unpublished_page(): void
    {
        CmsPage::query()->create([
            'title' => 'Draft',
            'slug' => 'secret-draft',
            'is_published' => false,
            'blocks' => $this->headingDocument('Coming soon'),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('shop.pages.preview', 'secret-draft'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Shop/CmsPage', false)
                ->where('page.blocks.sections.0.columns.0.widgets.0.settings.text', 'Coming soon')
            );
    }

    /**
     * @return array{sections: list<array<string, mixed>>}
     */
    private function headingDocument(string $text = 'Hello'): array
    {
        return $this->widgetDocument('heading', [
            'text' => $text,
            'tag' => 'h2',
            'align' => 'left',
            'color' => '#1e3a5f',
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{sections: list<array<string, mixed>>}
     */
    private function widgetDocument(string $type, array $settings): array
    {
        return [
            'sections' => [
                [
                    'id' => 'section-1',
                    'settings' => [
                        'layout' => '100',
                        'background' => '#ffffff',
                        'background_image' => '',
                        'padding' => 'md',
                        'full_width' => false,
                    ],
                    'columns' => [
                        [
                            'id' => 'column-1',
                            'width' => 100,
                            'widgets' => [
                                [
                                    'id' => 'widget-1',
                                    'type' => $type,
                                    'settings' => $settings,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function seedPublishedProduct(string $name): void
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
            'name' => $name,
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
    }
}
