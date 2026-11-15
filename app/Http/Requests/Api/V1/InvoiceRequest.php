<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Company;
use App\Rules\InTenant;
use App\Support\Api\MobileAccess;

class InvoiceRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return MobileAccess::managesCustomer($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = new InTenant(Company::class, $this->customerId(), 'La empresa seleccionada no es válida.');
        $money = ['nullable', 'numeric', 'min:0', 'max:999999999999999999.99'];
        $smallMoney = ['nullable', 'numeric', 'min:0', 'max:99999999.99'];
        $percentage = ['nullable', 'numeric', 'min:0', 'max:100'];

        return [
            'customer_id' => $this->customerRule(),
            'from_company_id' => ['required', 'integer', $company],
            'to_company_id' => ['required', 'integer', $company],
            'name' => ['required', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'total_amount' => $money,
            'discount_percentage' => $percentage,
            'discount_amount' => $smallMoney,
            'tax_percentage' => $percentage,
            'tax_amount' => $smallMoney,
            'final_amount' => $money,
            'payed_amount' => $money,
            'payed' => ['required', 'boolean'],
            'payment_due_date' => ['nullable', 'date_format:Y-m-d'],
            'items' => ['sometimes', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.discount_percentage' => $percentage,
            'items.*.discount_amount' => $smallMoney,
            'items.*.total_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'from_company_id.required' => 'Elegí la empresa emisora.',
            'to_company_id.required' => 'Elegí la empresa receptora.',
            'items.*.name.required' => 'Ingresá el nombre del concepto.',
            'items.*.quantity.required' => 'Ingresá la cantidad.',
            'items.*.unit_price.required' => 'Ingresá el precio unitario.',
            'items.*.total_price.required' => 'Ingresá el precio total.',
        ];
    }

    /**
     * Columns with a database default cannot be null, so blanks fall back to zero.
     *
     * @return array<string, mixed>
     */
    public function invoiceAttributes(): array
    {
        $attributes = $this->safe()->except(['items']);

        foreach (['discount_percentage', 'discount_amount', 'tax_percentage', 'tax_amount'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $attributes[$column] ??= 0;
            }
        }

        return $attributes;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function items(): ?array
    {
        if (! $this->has('items')) {
            return null;
        }

        return array_map(fn (array $item): array => [
            ...$item,
            'discount_percentage' => $item['discount_percentage'] ?? 0,
            'discount_amount' => $item['discount_amount'] ?? 0,
        ], array_values($this->validated('items', [])));
    }
}
