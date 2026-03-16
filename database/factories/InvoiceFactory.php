<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
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
            'from_company_id' => Company::factory(),
            'to_company_id' => Company::factory(),
            'name' => fake()->sentence(3),
            'date' => now(),
            'total_amount' => fake()->randomFloat(2, 10, 1000),
            'payed_amount' => 0,
            'payed' => false,
            'payment_due_date' => now()->addDays(30),
        ];
    }
}
