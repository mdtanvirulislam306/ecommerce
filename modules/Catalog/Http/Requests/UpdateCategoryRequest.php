<?php

namespace Modules\Catalog\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Models\Category;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'parent_id' => $this->input('parent_id') ?: null,
            'is_active' => $this->boolean('is_active'),
            'clear_image' => $this->boolean('clear_image'),
            'sort_order' => $this->input('sort_order') ?? 0,
        ];

        if ($this->boolean('clear_image')) {
            $merge['media_library_id'] = null;
        } elseif ($this->exists('media_library_id')) {
            $merge['media_library_id'] = $this->input('media_library_id') ?: null;
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::notIn([$category->id]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', TenantRule::unique('categories', 'slug')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'media_library_id' => [
                'nullable',
                'integer',
                Rule::exists('media_library_items', 'id'),
            ],
            'clear_image' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
