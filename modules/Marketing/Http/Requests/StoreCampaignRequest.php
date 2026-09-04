<?php

namespace Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Marketing\Enums\CampaignChannel;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject' => $this->input('subject') ?: null,
            'body' => $this->input('body') ?: null,
            'scheduled_at' => $this->input('scheduled_at') ?: null,
            'channel' => $this->input('channel') ?: CampaignChannel::Email->value,
            'audience_count' => $this->input('audience_count') ?: 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', Rule::enum(CampaignChannel::class)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'scheduled_at' => ['nullable', 'date'],
            'audience_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
