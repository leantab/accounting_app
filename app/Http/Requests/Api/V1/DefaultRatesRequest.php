<?php

namespace App\Http\Requests\Api\V1;

use App\Support\Api\MobileAccess;

class DefaultRatesRequest extends TenantRequest
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
        return [
            'customer_id' => $this->customerRule(),
            'rates' => ['present', 'array'],
            'rates.*' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'rates.*.numeric' => __('api.enter_a_number'),
            'rates.*.min' => 'La tarifa no puede ser negativa.',
        ];
    }
}
