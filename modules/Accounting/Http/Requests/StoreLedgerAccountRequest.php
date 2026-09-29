<?php

namespace Modules\Accounting\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Accounting\Enums\AccountType;

class StoreLedgerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'parent_id' => $this->input('parent_id') ?: null,
            'description' => $this->input('description') ?: null,
            'sort_order' => $this->input('sort_order') ?? 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', TenantRule::unique('ledger_accounts', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'parent_id' => ['nullable', 'integer', 'exists:ledger_accounts,id'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
