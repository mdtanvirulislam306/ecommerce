<?php

namespace Modules\Ecommerce\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Ecommerce\Services\PageBuilder\PageBuilderRegistry;

class StoreCmsPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', TenantRule::unique('cms_pages', 'slug')],
            'body' => ['nullable', 'string'],
            'blocks' => ['nullable', 'array'],
            'blocks.sections' => ['required_with:blocks', 'array'],
            'is_published' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! is_array($this->input('blocks'))) {
                    return;
                }

                $this->container->make(PageBuilderRegistry::class)
                    ->validateDocument($this->input('blocks'), $validator);
            },
        ];
    }
}
