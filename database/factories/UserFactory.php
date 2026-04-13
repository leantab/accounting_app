<?php

namespace Database\Factories;

use App\Enums\UserRoleEnum;
use App\Models\Customer;
use App\Models\User;
use App\Models\UserRole;
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
            'name' => fake()->name(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'is_active' => true,
            'customer_id' => null,
            'is_admin' => false,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn(array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn(array $attributes) => [
            'customer_id' => Customer::factory()->create()->id,
        ]);
    }

    public function withCustomer(): static
    {
        return $this->state(fn(array $attributes) => [
            'customer_id' => Customer::factory()->create()->id,
        ]);
    }

    public function customerEmployee(): static
    {
        return $this->state(fn(array $attributes) => [
            'customer_id' => Customer::factory()->create()->id,
            'is_admin' => false,
            'user_role_id' => UserRoleEnum::Employee->value,
        ]);
    }

    public function customerAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'customer_id' => Customer::factory()->create()->id,
            'is_admin' => false,
            'user_role_id' => UserRoleEnum::Admin->value,
        ]);
    }

    public function customerOwner(): static
    {
        return $this->state(fn(array $attributes) => [
            'customer_id' => Customer::factory()->create()->id,
            'is_admin' => false,
            'user_role_id' => UserRoleEnum::Owner->value,
        ]);
    }
}
