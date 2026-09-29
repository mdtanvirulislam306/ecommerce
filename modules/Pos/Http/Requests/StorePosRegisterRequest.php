<?php

namespace Modules\Pos\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePosRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'warehouse_id' => $this->input('warehouse_id') ?: null,
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => $this->input('sort_order') ?? 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', TenantRule::unique('pos_registers', 'code')],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
