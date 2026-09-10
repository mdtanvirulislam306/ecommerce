<?php

namespace Modules\Ecommerce\Services\PageBuilder;

use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Modules\Ecommerce\Models\CmsPage;
use Modules\Ecommerce\Services\StorefrontCatalogService;

class PageBuilderRegistry
{
    /**
     * @return list<array{code: string, label: string, widths: list<int>}>
     */
    public function layouts(): array
    {
        return [
            ['code' => '100', 'label' => '1 column', 'widths' => [100]],
            ['code' => '50-50', 'label' => '2 columns', 'widths' => [50, 50]],
            ['code' => '33-33-33', 'label' => '3 columns', 'widths' => [33, 34, 33]],
            ['code' => '25-25-25-25', 'label' => '4 columns', 'widths' => [25, 25, 25, 25]],
            ['code' => '33-67', 'label' => '1/3 + 2/3', 'widths' => [33, 67]],
            ['code' => '67-33', 'label' => '2/3 + 1/3', 'widths' => [67, 33]],
        ];
    }

    /**
     * @return list<array{type: string, label: string, category: string, defaults: array<string, mixed>}>
     */
    public function catalog(): array
    {
        $items = [];

        foreach ($this->definitions() as $type => $definition) {
            $items[] = [
                'type' => $type,
                'label' => $definition['label'],
                'category' => $definition['category'],
                'defaults' => $definition['defaults'],
            ];
        }

        return $items;
    }

    /**
     * @return array{sections: list<array<string, mixed>>}
     */
    public function emptyDocument(): array
    {
        return ['sections' => []];
    }

    /**
     * @return array{sections: list<array<string, mixed>>}
     */
    public function documentForEditor(CmsPage $page): array
    {
        $blocks = $page->blocks;

        if (is_array($blocks) && $this->hasSections($blocks)) {
            return $this->normalizeDocument($blocks);
        }

        if (filled($page->body)) {
            return $this->documentFromLegacyBody((string) $page->body);
        }

        if (is_array($blocks) && isset($blocks['sections']) && is_array($blocks['sections'])) {
            return $this->normalizeDocument($blocks);
        }

        return $this->emptyDocument();
    }

    /**
     * @return array{sections: list<array<string, mixed>>}
     */
    public function documentFromLegacyBody(string $body): array
    {
        return [
            'sections' => [
                $this->makeSection('100', [
                    $this->makeColumn(100, [
                        $this->makeWidget('html', ['content' => $body]),
                    ]),
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array{sections: list<array<string, mixed>>}
     */
    public function normalizeDocument(array $document): array
    {
        $sections = [];

        foreach ($document['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $sections[] = $this->normalizeSection($section);
        }

        return ['sections' => $sections];
    }

    public function validateDocument(array $document, Validator $validator): void
    {
        if (! isset($document['sections']) || ! is_array($document['sections'])) {
            $validator->errors()->add('blocks.sections', 'The page layout must contain a sections array.');

            return;
        }

        $layoutCodes = array_column($this->layouts(), 'code');
        $widgetTypes = array_keys($this->definitions());

        foreach ($document['sections'] as $sectionIndex => $section) {
            $sectionPath = "blocks.sections.{$sectionIndex}";

            if (! is_array($section)) {
                $validator->errors()->add($sectionPath, 'Each section must be an object.');

                continue;
            }

            if (! is_string($section['id'] ?? null) || $section['id'] === '') {
                $validator->errors()->add("{$sectionPath}.id", 'Each section needs an id.');
            }

            $layout = is_array($section['settings'] ?? null)
                ? (string) ($section['settings']['layout'] ?? '100')
                : '100';

            if (! in_array($layout, $layoutCodes, true)) {
                $validator->errors()->add("{$sectionPath}.settings.layout", 'The selected column layout is invalid.');
            }

            $columns = $section['columns'] ?? null;

            if (! is_array($columns) || $columns === []) {
                $validator->errors()->add("{$sectionPath}.columns", 'Each section needs at least one column.');

                continue;
            }

            $expected = $this->layoutWidths($layout);

            if (count($columns) !== count($expected)) {
                $validator->errors()->add(
                    "{$sectionPath}.columns",
                    'Column count must match the selected layout.',
                );
            }

            foreach ($columns as $columnIndex => $column) {
                $columnPath = "{$sectionPath}.columns.{$columnIndex}";

                if (! is_array($column)) {
                    $validator->errors()->add($columnPath, 'Each column must be an object.');

                    continue;
                }

                if (! is_string($column['id'] ?? null) || $column['id'] === '') {
                    $validator->errors()->add("{$columnPath}.id", 'Each column needs an id.');
                }

                $widgets = $column['widgets'] ?? null;

                if (! is_array($widgets)) {
                    $validator->errors()->add("{$columnPath}.widgets", 'Each column needs a widgets array.');

                    continue;
                }

                foreach ($widgets as $widgetIndex => $widget) {
                    $widgetPath = "{$columnPath}.widgets.{$widgetIndex}";

                    if (! is_array($widget)) {
                        $validator->errors()->add($widgetPath, 'Each widget must be an object.');

                        continue;
                    }

                    if (! is_string($widget['id'] ?? null) || $widget['id'] === '') {
                        $validator->errors()->add("{$widgetPath}.id", 'Each widget needs an id.');
                    }

                    $type = $widget['type'] ?? null;

                    if (! is_string($type) || ! in_array($type, $widgetTypes, true)) {
                        $validator->errors()->add("{$widgetPath}.type", 'Unknown widget type.');

                        continue;
                    }

                    $settings = is_array($widget['settings'] ?? null) ? $widget['settings'] : [];
                    $this->validateWidgetSettings($type, $settings, $widgetPath, $validator);
                }
            }
        }
    }

    /**
     * @param  array{sections: list<array<string, mixed>>}  $document
     * @return array{sections: list<array<string, mixed>>}
     */
    public function hydrate(array $document, StorefrontCatalogService $catalog): array
    {
        $normalized = $this->normalizeDocument($document);

        foreach ($normalized['sections'] as &$section) {
            foreach ($section['columns'] as &$column) {
                foreach ($column['widgets'] as &$widget) {
                    $widget = $this->hydrateWidget($widget, $catalog);
                }
            }
        }

        unset($section, $column, $widget);

        return $normalized;
    }

    public function findWidget(array $document, string $widgetId): ?array
    {
        foreach ($document['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }

            foreach ($section['columns'] ?? [] as $column) {
                if (! is_array($column)) {
                    continue;
                }

                foreach ($column['widgets'] ?? [] as $widget) {
                    if (is_array($widget) && ($widget['id'] ?? null) === $widgetId) {
                        return $widget;
                    }
                }
            }
        }

        return null;
    }

    public function isAllowedVideoUrl(string $url): bool
    {
        return $this->videoEmbedUrl($url) !== null;
    }

    public function isAllowedLink(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        return in_array(strtolower($parts['scheme']), ['http', 'https'], true);
    }

    public function sanitizeHtml(string $html): string
    {
        $stripped = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $stripped = strip_tags($stripped, '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><h4><blockquote><span>');
        $stripped = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', 'href="#"', $stripped) ?? $stripped;

        return $stripped;
    }

    public function videoEmbedUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '' || ! $this->isAllowedLink($url) || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = Str::of($host)->replaceStart('www.', '')->toString();
        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (in_array($host, ['youtube.com', 'youtube-nocookie.com', 'm.youtube.com'], true)) {
            $id = $query['v'] ?? null;

            if (! is_string($id) || $id === '') {
                if (preg_match('#/(embed|shorts)/([A-Za-z0-9_-]{6,})#', $path, $matches) === 1) {
                    $id = $matches[2];
                }
            }

            if (is_string($id) && $id !== '') {
                return 'https://www.youtube-nocookie.com/embed/'.rawurlencode($id);
            }
        }

        if ($host === 'youtu.be') {
            $id = ltrim($path, '/');

            if ($id !== '') {
                return 'https://www.youtube-nocookie.com/embed/'.rawurlencode($id);
            }
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            if (preg_match('#/(\d+)#', $path, $matches) === 1) {
                return 'https://player.vimeo.com/video/'.$matches[1];
            }
        }

        return null;
    }

    /**
     * @return array<string, array{label: string, category: string, defaults: array<string, mixed>}>
     */
    private function definitions(): array
    {
        return [
            'heading' => [
                'label' => 'Heading',
                'category' => 'content',
                'defaults' => [
                    'text' => 'Heading',
                    'tag' => 'h2',
                    'align' => 'left',
                    'color' => '#1e3a5f',
                ],
            ],
            'text' => [
                'label' => 'Text',
                'category' => 'content',
                'defaults' => [
                    'content' => 'Write your paragraph here.',
                    'align' => 'left',
                ],
            ],
            'image' => [
                'label' => 'Image',
                'category' => 'content',
                'defaults' => [
                    'url' => '',
                    'alt' => '',
                    'media_id' => null,
                    'width' => 'full',
                ],
            ],
            'button' => [
                'label' => 'Button',
                'category' => 'content',
                'defaults' => [
                    'label' => 'Shop now',
                    'url' => '/shop',
                    'align' => 'left',
                    'style' => 'primary',
                ],
            ],
            'spacer' => [
                'label' => 'Spacer',
                'category' => 'content',
                'defaults' => [
                    'height' => 32,
                ],
            ],
            'video' => [
                'label' => 'Video',
                'category' => 'content',
                'defaults' => [
                    'url' => '',
                ],
            ],
            'html' => [
                'label' => 'HTML',
                'category' => 'content',
                'defaults' => [
                    'content' => '',
                ],
            ],
            'hero' => [
                'label' => 'Hero banner',
                'category' => 'shop',
                'defaults' => [
                    'title' => 'Discover the collection',
                    'subtitle' => 'Hand-picked products for everyday life.',
                    'image_url' => '',
                    'media_id' => null,
                    'cta_label' => 'Browse shop',
                    'cta_url' => '/shop',
                    'align' => 'left',
                    'overlay' => 40,
                ],
            ],
            'product_grid' => [
                'label' => 'Product grid',
                'category' => 'shop',
                'defaults' => [
                    'heading' => 'Products',
                    'source' => 'latest',
                    'limit' => 8,
                    'columns' => 4,
                ],
            ],
            'featured_products' => [
                'label' => 'Featured products',
                'category' => 'shop',
                'defaults' => [
                    'heading' => 'Featured',
                    'source' => 'featured',
                    'limit' => 8,
                    'columns' => 4,
                ],
            ],
            'categories' => [
                'label' => 'Categories',
                'category' => 'shop',
                'defaults' => [
                    'heading' => 'Shop by category',
                    'limit' => 8,
                ],
            ],
            'testimonials' => [
                'label' => 'Testimonials',
                'category' => 'extra',
                'defaults' => [
                    'heading' => 'What customers say',
                    'items' => [
                        ['quote' => 'Great quality and fast delivery.', 'name' => 'Ayesha', 'role' => 'Customer', 'avatar_url' => ''],
                    ],
                ],
            ],
            'faq' => [
                'label' => 'FAQ',
                'category' => 'extra',
                'defaults' => [
                    'heading' => 'Frequently asked questions',
                    'items' => [
                        ['question' => 'How long does shipping take?', 'answer' => 'Most orders arrive within 3–5 business days.'],
                    ],
                ],
            ],
            'newsletter' => [
                'label' => 'Newsletter',
                'category' => 'extra',
                'defaults' => [
                    'heading' => 'Get updates',
                    'placeholder' => 'Email address',
                    'button_label' => 'Subscribe',
                ],
            ],
            'contact_form' => [
                'label' => 'Contact form',
                'category' => 'extra',
                'defaults' => [
                    'heading' => 'Contact us',
                    'button_label' => 'Send message',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateWidgetSettings(string $type, array $settings, string $widgetPath, Validator $validator): void
    {
        if (in_array($type, ['button', 'hero'], true)) {
            $url = $type === 'hero'
                ? (string) ($settings['cta_url'] ?? '')
                : (string) ($settings['url'] ?? '');

            if ($url !== '' && ! $this->isAllowedLink($url)) {
                $key = $type === 'hero' ? 'cta_url' : 'url';
                $validator->errors()->add("{$widgetPath}.settings.{$key}", 'Enter a valid http(s) or site-relative link.');
            }
        }

        if ($type === 'video') {
            $url = trim((string) ($settings['url'] ?? ''));

            if ($url !== '' && ! $this->isAllowedVideoUrl($url)) {
                $validator->errors()->add("{$widgetPath}.settings.url", 'Enter a YouTube or Vimeo URL.');
            }
        }

        if ($type === 'product_grid' || $type === 'featured_products') {
            $source = (string) ($settings['source'] ?? 'latest');

            if (! in_array($source, ['featured', 'homepage', 'latest'], true)) {
                $validator->errors()->add("{$widgetPath}.settings.source", 'The product source is invalid.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $widget
     * @return array<string, mixed>
     */
    private function hydrateWidget(array $widget, StorefrontCatalogService $catalog): array
    {
        $type = (string) ($widget['type'] ?? '');
        $settings = is_array($widget['settings'] ?? null) ? $widget['settings'] : [];
        $widget['data'] = [];

        if ($type === 'html') {
            $widget['data']['html'] = $this->sanitizeHtml((string) ($settings['content'] ?? ''));
        }

        if ($type === 'video') {
            $widget['data']['embed_url'] = $this->videoEmbedUrl((string) ($settings['url'] ?? ''));
        }

        if ($type === 'product_grid' || $type === 'featured_products') {
            $limit = max(1, min(24, (int) ($settings['limit'] ?? 8)));
            $source = $type === 'featured_products'
                ? 'featured'
                : (string) ($settings['source'] ?? 'latest');

            $widget['data']['products'] = match ($source) {
                'featured' => $catalog->featuredProducts($limit),
                'homepage' => $catalog->homepageProducts($limit),
                default => $catalog->publishedProducts($limit),
            };
        }

        if ($type === 'categories') {
            $limit = max(1, min(24, (int) ($settings['limit'] ?? 8)));
            $widget['data']['categories'] = $catalog->activeCategories($limit);
        }

        return $widget;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function normalizeSection(array $section): array
    {
        $settings = is_array($section['settings'] ?? null) ? $section['settings'] : [];
        $layout = (string) ($settings['layout'] ?? '100');
        $widths = $this->layoutWidths($layout);
        $incoming = is_array($section['columns'] ?? null) ? array_values($section['columns']) : [];
        $columns = [];

        foreach ($widths as $index => $width) {
            $column = is_array($incoming[$index] ?? null) ? $incoming[$index] : [];
            $columns[] = $this->normalizeColumn($column, $width);
        }

        return [
            'id' => $this->stringId($section['id'] ?? null),
            'settings' => [
                'layout' => $layout,
                'background' => (string) ($settings['background'] ?? '#ffffff'),
                'background_image' => (string) ($settings['background_image'] ?? ''),
                'padding' => $this->padding((string) ($settings['padding'] ?? 'md')),
                'full_width' => (bool) ($settings['full_width'] ?? false),
            ],
            'columns' => $columns,
        ];
    }

    /**
     * @param  array<string, mixed>  $column
     * @return array<string, mixed>
     */
    private function normalizeColumn(array $column, int $width): array
    {
        $widgets = [];

        foreach ($column['widgets'] ?? [] as $widget) {
            if (! is_array($widget)) {
                continue;
            }

            $type = (string) ($widget['type'] ?? '');

            if (! array_key_exists($type, $this->definitions())) {
                continue;
            }

            $widgets[] = $this->normalizeWidget($widget, $type);
        }

        return [
            'id' => $this->stringId($column['id'] ?? null),
            'width' => $width,
            'widgets' => $widgets,
        ];
    }

    /**
     * @param  array<string, mixed>  $widget
     * @return array<string, mixed>
     */
    private function normalizeWidget(array $widget, string $type): array
    {
        $defaults = $this->definitions()[$type]['defaults'];
        $settings = is_array($widget['settings'] ?? null) ? $widget['settings'] : [];

        return [
            'id' => $this->stringId($widget['id'] ?? null),
            'type' => $type,
            'settings' => array_merge($defaults, $settings),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @return array<string, mixed>
     */
    private function makeSection(string $layout, array $columns): array
    {
        return [
            'id' => $this->stringId(null),
            'settings' => [
                'layout' => $layout,
                'background' => '#ffffff',
                'background_image' => '',
                'padding' => 'md',
                'full_width' => false,
            ],
            'columns' => $columns,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $widgets
     * @return array<string, mixed>
     */
    private function makeColumn(int $width, array $widgets): array
    {
        return [
            'id' => $this->stringId(null),
            'width' => $width,
            'widgets' => $widgets,
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function makeWidget(string $type, array $settings = []): array
    {
        $defaults = $this->definitions()[$type]['defaults'];

        return [
            'id' => $this->stringId(null),
            'type' => $type,
            'settings' => array_merge($defaults, $settings),
        ];
    }

    /**
     * @return list<int>
     */
    private function layoutWidths(string $layout): array
    {
        foreach ($this->layouts() as $item) {
            if ($item['code'] === $layout) {
                return $item['widths'];
            }
        }

        return [100];
    }

    private function padding(string $value): string
    {
        return in_array($value, ['none', 'sm', 'md', 'lg', 'xl'], true) ? $value : 'md';
    }

    private function stringId(mixed $value): string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return (string) Str::uuid();
    }

    /**
     * @param  array<string, mixed>  $blocks
     */
    private function hasSections(array $blocks): bool
    {
        return isset($blocks['sections']) && is_array($blocks['sections']) && $blocks['sections'] !== [];
    }
}
