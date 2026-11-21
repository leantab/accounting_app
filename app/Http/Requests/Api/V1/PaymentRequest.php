<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Company;
use App\Models\Invoice;
use App\Rules\InTenant;
use App\Support\Api\MobileAccess;

class PaymentRequest extends TenantRequest
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
        $company = new InTenant(Company::class, $this->customerId(), __('api.invalid_company'));

        return [
            'customer_id' => $this->customerRule(),
            'from_company_id' => ['required', 'integer', $company],
            'to_company_id' => ['required', 'integer', $company],
            'invoice_id' => ['nullable', 'integer', new InTenant(Invoice::class, $this->customerId(), __('api.invalid_invoice'))],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'from_company_id.required' => __('api.choose_paying_company'),
            'to_company_id.required' => __('api.choose_collecting_company'),
            'reference.required' => __('api.enter_reference'),
            'amount.required' => __('api.enter_amount'),
        ];
    }
}
