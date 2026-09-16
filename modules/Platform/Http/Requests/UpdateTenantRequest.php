<?php

namespace Modules\Platform\Http\Requests;

use App\Models\TenantDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_platform_admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'in:active,trial,suspended'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'domain' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('tenant_domains', 'domain')->ignore(
                    TenantDomain::query()->where('tenant_id', $tenantId)->where('is_primary', true)->value('id')
                ),
            ],
            'plan_id' => ['sometimes', 'integer', 'exists:plans,id'],
            'payment_note' => ['nullable', 'string', 'max:2000'],
            'ends_at' => ['nullable', 'date'],
            'module_overrides' => ['nullable', 'array'],
            'module_overrides.*' => ['boolean'],
            'owner_password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
