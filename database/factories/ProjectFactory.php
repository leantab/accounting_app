<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'customer_id' => Customer::factory(),
            'company_id' => Company::factory(),
            'description' => $this->faker->sentence(),
            'start_date' => now(),
            'project_status_id' => ProjectStatus::firstOrCreate(['name' => 'Active'])->id,
        ];
    }
}
