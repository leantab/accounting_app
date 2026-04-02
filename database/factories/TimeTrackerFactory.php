<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeTrackerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'name' => $this->faker->words(3, true),
            'date_start' => now(),
            'date_end' => now()->addDays(5),
            'hours' => 10,
            'description' => $this->faker->sentence(),
            'billed' => false,
            'amount' => 1000,
            'invoice_id' => Invoice::factory(),
            'paid' => false,
            'payment_id' => Payment::factory(),
            'approved_by' => User::factory(),
        ];
    }
}
