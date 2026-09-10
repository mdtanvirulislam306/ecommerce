<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCmsPageFormRequest extends FormRequest
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
            'type' => ['required', 'in:contact,newsletter'],
            'widget_id' => ['required', 'string', 'max:64'],
            'name' => ['required_if:type,contact', 'nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required_if:type,contact', 'nullable', 'string', 'max:5000'],
        ];
    }
}
