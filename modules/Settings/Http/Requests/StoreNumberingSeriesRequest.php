<?php

namespace Modules\Settings\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNumberingSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', TenantRule::unique('numbering_series', 'code')],
            'prefix' => ['nullable', 'string', 'max:20'],
            'next_number' => ['required', 'integer', 'min:1'],
            'pad_length' => ['required', 'integer', 'min:1', 'max:12'],
            'is_active' => ['boolean'],
        ];
    }
}
