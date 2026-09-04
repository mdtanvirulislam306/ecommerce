<?php

namespace Modules\Crm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Crm\Enums\LeadStage;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->input('email') ?: null,
            'phone' => $this->input('phone') ?: null,
            'company' => $this->input('company') ?: null,
            'source' => $this->input('source') ?: null,
            'source_id' => $this->input('source_id') ?: null,
            'customer_group_id' => $this->input('customer_group_id') ?: null,
            'notes' => $this->input('notes') ?: null,
            'assigned_to' => $this->input('assigned_to') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:80'],
            'source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'stage' => ['nullable', Rule::in([
                LeadStage::New->value,
                LeadStage::Contacted->value,
                LeadStage::Qualified->value,
                LeadStage::Proposal->value,
            ])],
            'customer_group_id' => ['nullable', 'integer', 'exists:customer_groups,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
