<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
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
            'invoice_number' => fake()->unique()->bothify('INV-####-????'),
            'description' => fake()->optional()->sentence(),
            'date' => now(),
            'total_amount' => function (array $attributes) {
                return fake()->randomFloat(2, 10, 1000);
            },
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'final_amount' => fn (array $attributes) => $attributes['total_amount'],
            'payed_amount' => 0,
            'payed' => false,
            'payment_due_date' => now()->addDays(30),
        ];
    }
}
