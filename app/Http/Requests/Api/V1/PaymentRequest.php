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
        $company = new InTenant(Company::class, $this->customerId(), 'La empresa seleccionada no es válida.');

        return [
            'customer_id' => $this->customerRule(),
            'from_company_id' => ['required', 'integer', $company],
            'to_company_id' => ['required', 'integer', $company],
            'invoice_id' => ['nullable', 'integer', new InTenant(Invoice::class, $this->customerId(), 'La factura seleccionada no es válida.')],
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
            'from_company_id.required' => 'Elegí la empresa que paga.',
            'to_company_id.required' => 'Elegí la empresa que cobra.',
            'reference.required' => 'Ingresá una referencia.',
            'amount.required' => 'Ingresá el monto.',
        ];
    }
}
