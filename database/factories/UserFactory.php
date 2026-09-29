<?php

namespace Database\Factories;

use App\Core\Tenant\TenantContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => app(TenantContext::class)->id(),
            'is_owner' => true,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * A platform owner: belongs to no shop and can manage every shop.
     */
    public function platformAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'is_platform_admin' => true,
            'is_owner' => false,
        ]);
    }

    /**
     * A shop staff member whose access comes only from assigned roles.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_owner' => false,
        ]);
    }

    /**
     * Staff who were invited but have not set their password yet.
     */
    public function invited(?string $plainToken = null): static
    {
        return $this->staff()->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'invited_at' => now(),
            'invitation_token' => hash('sha256', $plainToken ?? Str::random(40)),
        ]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
