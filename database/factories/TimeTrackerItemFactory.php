<?php

namespace Database\Factories;

use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeTrackerItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'time_tracker_id' => TimeTracker::factory(),
            'time_tracker_item_type_id' => TimeTrackerItemType::firstOrCreate(['name' => 'General'])->id,
            'date' => now(),
            'hours' => 5,
            'description' => $this->faker->sentence(),
            'rate' => 100,
            'amount' => 500,
        ];
    }
}
