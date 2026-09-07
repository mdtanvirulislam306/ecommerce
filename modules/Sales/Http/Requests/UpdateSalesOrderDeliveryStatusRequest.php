<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Sales\Enums\SalesDeliveryStatus;

class UpdateSalesOrderDeliveryStatusRequest extends FormRequest
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
            'delivery_status' => ['required', Rule::enum(SalesDeliveryStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
