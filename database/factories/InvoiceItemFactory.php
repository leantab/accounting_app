<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
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
            'invoice_id' => Invoice::factory(),
            'name' => fake()->sentence(2),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_price' => fake()->randomFloat(2, 1, 100),
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'total_price' => fn(array $attributes) => ($attributes['quantity'] * $attributes['unit_price']) - $attributes['discount_amount'],
        ];
    }
}
