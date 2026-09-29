<?php

namespace Modules\Ecommerce\Models;

use App\Core\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Crm\Models\Customer;
use Modules\Ecommerce\Enums\OnlineOrderStatus;
use Modules\Ecommerce\Enums\PaymentMethod;
use Modules\Ecommerce\Enums\PaymentStatus;

class OnlineOrder extends Model
{
    use BelongsToTenant;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'payment_status' => 'unpaid',
    ];

    protected $fillable = [
        'number',
        'access_token',
        'status',
        'customer_id',
        'customer_account_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'payment_method',
        'payment_status',
        'payment_transaction_id',
        'payment_validation_id',
        'payment_bank_transaction_id',
        'payment_card_type',
        'paid_at',
        'currency',
        'subtotal',
        'coupon_code',
        'discount_total',
        'delivery_zone',
        'shipping_fee',
        'grand_total',
        'notes',
        'warehouse_id',
        'confirmed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OnlineOrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'shipping_fee' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnlineOrderItem::class)->orderBy('sort_order');
    }
}
