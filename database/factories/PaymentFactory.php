<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'from_company_id' => Company::factory(),
            'to_company_id' => Company::factory(),
            'reference' => fake()->uuid(),
            'amount' => fake()->randomFloat(2, 10, 1000),
        ];
    }
}
