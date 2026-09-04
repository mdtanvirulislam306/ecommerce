<?php

namespace Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'starts_at' => $this->input('starts_at') ?: null,
            'expires_at' => $this->input('expires_at') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'type' => ['required', Rule::in(['image', 'video'])],
            'media' => [
                'required',
                'file',
                $this->input('type') === 'video'
                    ? 'mimes:mp4,webm,mov|max:51200'
                    : 'mimes:jpeg,jpg,png,webp|max:10240',
            ],
            'action_url' => ['nullable', 'url', 'max:2048'],
            'action_label' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => [
                'nullable',
                'date',
                Rule::when(
                    $this->filled('starts_at'),
                    'after:starts_at',
                ),
            ],
        ];
    }
}
