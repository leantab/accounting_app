<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
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
            'invoice_id' => Invoice::factory(),
            'name' => fake()->sentence(2),
            'amount' => fake()->numberBetween(1, 10),
            'unit_price' => fake()->randomFloat(2, 1, 100),
            'total_price' => fn (array $attributes) => $attributes['amount'] * $attributes['unit_price'],
        ];
    }
}
