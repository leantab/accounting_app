<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for requests that write records owned by a customer.
 */
abstract class TenantRequest extends FormRequest
{
    /**
     * The customer the record belongs to: chosen by platform admins, fixed for everyone else.
     */
    public function customerId(): ?int
    {
        /** @var User $user */
        $user = $this->user();

        if (! $user->is_admin) {
            return $user->customer_id;
        }

        return $this->filled('customer_id') ? (int) $this->input('customer_id') : null;
    }

    /**
     * Platform admins must choose the customer; for everyone else the field is dropped and the tenant scope fills it.
     *
     * @return list<string>
     */
    protected function customerRule(): array
    {
        /** @var User $user */
        $user = $this->user();

        return $user->is_admin ? ['required', 'integer', 'exists:customers,id'] : ['exclude'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Elegí un cliente.',
            'name.required' => 'Ingresá un nombre.',
            'email.email' => 'Ingresá un email válido.',
            'required' => 'Completá este campo.',
            'numeric' => 'Ingresá un número.',
            'integer' => 'Ingresá un número entero.',
            'min' => 'El valor es demasiado bajo.',
            'max' => 'El valor es demasiado largo.',
            'date_format' => 'La fecha no es válida.',
        ];
    }
}
