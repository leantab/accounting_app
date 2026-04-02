<?php

namespace Database\Factories;

use App\Models\CompanyRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyRole>
 */
class CompanyRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->jobTitle(),
        ];
    }
}
