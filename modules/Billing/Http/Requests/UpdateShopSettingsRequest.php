<?php

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopSettingsRequest extends FormRequest
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
            'multi_price' => ['boolean'],
            'multi_warehouse' => ['boolean'],
            'multi_branch' => ['boolean'],
        ];
    }
}
