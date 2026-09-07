<?php

namespace Modules\Files\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required_without:files', 'nullable', 'file', 'max:10240'],
            'files' => ['required_without:file', 'nullable', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', 'max:10240'],
        ];
    }
}
