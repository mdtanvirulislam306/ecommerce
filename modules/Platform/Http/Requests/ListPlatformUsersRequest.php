<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Platform\Services\PlatformUserService;
use Modules\Settings\Enums\StaffStatus;

class ListPlatformUsersRequest extends FormRequest
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
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'type' => ['nullable', Rule::in([PlatformUserService::TYPE_OWNER, PlatformUserService::TYPE_STAFF, PlatformUserService::TYPE_PLATFORM_ADMIN])],
            'status' => ['nullable', Rule::enum(StaffStatus::class)],
        ];
    }

    /**
     * @return array{search: string|null, tenant_id: int|null, type: string|null, status: string|null}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => $validated['search'] ?? null,
            'tenant_id' => isset($validated['tenant_id']) ? (int) $validated['tenant_id'] : null,
            'type' => $validated['type'] ?? null,
            'status' => $validated['status'] ?? null,
        ];
    }
}
