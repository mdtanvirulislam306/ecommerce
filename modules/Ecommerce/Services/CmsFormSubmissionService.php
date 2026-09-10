<?php

namespace Modules\Ecommerce\Services;

use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Models\CmsFormSubmission;
use Modules\Ecommerce\Models\CmsPage;
use Modules\Ecommerce\Services\PageBuilder\PageBuilderRegistry;

class CmsFormSubmissionService extends Service
{
    public function __construct(private readonly PageBuilderRegistry $registry) {}

    /**
     * @param  array{type: string, widget_id: string, name?: string|null, email: string, message?: string|null}  $data
     */
    public function submit(CmsPage $page, array $data, ?string $ip): CmsFormSubmission
    {
        $document = is_array($page->blocks) ? $page->blocks : $this->registry->emptyDocument();
        $widget = $this->registry->findWidget($document, $data['widget_id']);
        $expectedType = $data['type'] === 'newsletter' ? 'newsletter' : 'contact_form';

        if ($widget === null || ($widget['type'] ?? null) !== $expectedType) {
            throw ValidationException::withMessages([
                'widget_id' => 'This form is no longer available on the page.',
            ]);
        }

        $payload = [
            'email' => $data['email'],
        ];

        if ($data['type'] === 'contact') {
            $payload['name'] = $data['name'] ?? '';
            $payload['message'] = $data['message'] ?? '';
        }

        return DB::transaction(fn () => CmsFormSubmission::query()->create([
            'cms_page_id' => $page->id,
            'widget_id' => $data['widget_id'],
            'type' => $data['type'],
            'payload' => $payload,
            'ip' => $ip,
        ]));
    }
}
