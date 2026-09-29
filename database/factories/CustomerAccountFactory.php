<?php

namespace Database\Factories;

use App\Core\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\CustomerAccount;

/**
 * @extends Factory<CustomerAccount>
 */
class CustomerAccountFactory extends Factory
{
    protected $model = CustomerAccount::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => app(TenantContext::class)->id(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '017'.fake()->numerify('########'),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function withSavedAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_address' => 'House 12, Road 5, Dhanmondi',
            'default_district' => 'Dhaka',
        ]);
    }
}
