<?php

namespace Database\Factories;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\TimeTrackerItemType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DefaultRate>
 */
class DefaultRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'time_tracker_item_type_id' => TimeTrackerItemType::query()->firstOrCreate([
                'name' => TimeTrackerItemTypeEnum::HOURS->label(),
            ])->id,
            'rate' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
