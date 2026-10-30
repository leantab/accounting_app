<?php

namespace Database\Factories;

use App\Enums\CheckInTypeEnum;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckInFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'project_id' => null,
            'type' => CheckInTypeEnum::In,
            'recorded_at' => now(),
            'latitude' => -34.6037,
            'longitude' => -58.3816,
            'accuracy_m' => 15,
        ];
    }

    public function out(): static
    {
        return $this->state(fn (): array => ['type' => CheckInTypeEnum::Out]);
    }
}
