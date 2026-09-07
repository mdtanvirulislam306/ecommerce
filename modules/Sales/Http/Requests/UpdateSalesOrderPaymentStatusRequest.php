<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Sales\Enums\SalesPaymentStatus;

class UpdateSalesOrderPaymentStatusRequest extends FormRequest
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
            'payment_status' => ['required', Rule::enum(SalesPaymentStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
