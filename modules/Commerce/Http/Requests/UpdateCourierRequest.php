<?php

namespace Modules\Commerce\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Commerce\Models\Courier;

class UpdateCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Courier $courier */
        $courier = $this->route('courier');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', TenantRule::unique('couriers', 'code')->ignore($courier->id)],
            'tracking_url_template' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
