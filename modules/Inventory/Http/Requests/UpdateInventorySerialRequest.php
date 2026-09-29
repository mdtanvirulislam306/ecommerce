<?php

namespace Modules\Inventory\Http\Requests;

use App\Core\Tenant\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Inventory\Models\InventorySerialNumber;

class UpdateInventorySerialRequest extends FormRequest
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
        /** @var InventorySerialNumber $serial */
        $serial = $this->route('inventorySerialNumber');

        return [
            'serial_number' => ['required', 'string', 'max:120', TenantRule::unique('inventory_serial_numbers', 'serial_number')->ignore($serial->id)],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'status' => ['required', Rule::in(['in_stock', 'sold', 'damaged', 'returned'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
