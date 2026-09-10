<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\CmsPage;
use Tests\TestCase;

class CmsPageBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_builder_request_redirects_to_login(): void
    {
        $this->get(route('ecommerce.pages.builder'))
            ->assertRedirect(route('login'));
    }

    public function test_builder_page_renders_widget_catalog(): void
    {
        $this->actingAs($this->user)
            ->get(route('ecommerce.pages.builder'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ecommerce/Cms/Pages/Builder', false)
                ->has('widgetCatalog')
                ->has('layouts')
                ->where('page', null)
            );
    }

    public function test_admin_can_save_a_valid_builder_document(): void
    {
        $response = $this->actingAs($this->user)->post(route('ecommerce.pages.builder.store'), [
            'title' => 'About us',
            'slug' => 'about',
            'is_published' => true,
            'seo_title' => 'About',
            'seo_description' => 'Our story',
            'blocks' => $this->headingDocument('Hello world'),
        ]);

        $page = CmsPage::query()->where('slug', 'about')->first();

        $response->assertRedirect(route('ecommerce.pages.builder.edit', $page));
        $this->assertNotNull($page);
        $this->assertSame('About us', $page->title);
        $this->assertTrue($page->is_published);
        $this->assertSame(
            'Hello world',
            $page->blocks['sections'][0]['columns'][0]['widgets'][0]['settings']['text'],
        );
    }

    public function test_unknown_widget_type_is_rejected(): void
    {
        $document = $this->headingDocument();
        $document['sections'][0]['columns'][0]['widgets'][0]['type'] = 'not-a-widget';

        $response = $this->actingAs($this->user)->from(route('ecommerce.pages.builder'))->post(route('ecommerce.pages.builder.store'), [
            'title' => 'Broken',
            'blocks' => $document,
        ]);

        $response->assertRedirect(route('ecommerce.pages.builder'));
        $response->assertSessionHasErrors([
            'blocks.sections.0.columns.0.widgets.0.type' => 'Unknown widget type.',
        ]);
        $this->assertSame(0, CmsPage::query()->count());
    }

    public function test_invalid_video_url_is_rejected(): void
    {
        $page = CmsPage::query()->create([
            'title' => 'Landing',
            'slug' => 'landing',
            'is_published' => false,
            'blocks' => $this->headingDocument(),
        ]);

        $document = $this->widgetDocument('video', [
            'url' => 'https://example.com/not-allowed',
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('ecommerce.pages.builder.edit', $page))
            ->put(route('ecommerce.pages.builder.update', $page), [
                'title' => 'Landing',
                'slug' => 'landing',
                'blocks' => $document,
            ]);

        $response->assertRedirect(route('ecommerce.pages.builder.edit', $page));
        $response->assertSessionHasErrors([
            'blocks.sections.0.columns.0.widgets.0.settings.url' => 'Enter a YouTube or Vimeo URL.',
        ]);

        $this->assertSame(
            'heading',
            $page->fresh()->blocks['sections'][0]['columns'][0]['widgets'][0]['type'],
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
}
