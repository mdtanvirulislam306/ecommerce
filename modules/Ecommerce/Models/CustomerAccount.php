<?php

namespace Modules\Ecommerce\Models;

use App\Core\Support\BelongsToTenant;
use Database\Factories\CustomerAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Crm\Models\Customer;
use Modules\Ecommerce\Notifications\ResetCustomerPasswordNotification;

/**
 * A shopper's storefront login. Separate from staff users so a customer can never reach the admin.
 */
class CustomerAccount extends Authenticatable
{
    /** @use HasFactory<CustomerAccountFactory> */
    use BelongsToTenant, HasFactory, Notifiable;

    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'phone',
        'password',
        'default_address',
        'default_district',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CustomerAccountFactory
    {
        return CustomerAccountFactory::new();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OnlineOrder::class);
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetCustomerPasswordNotification($token));
    }
}
