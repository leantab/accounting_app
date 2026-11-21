<?php

namespace App\Http\Requests\Api\V1;

use App\Support\Api\MobileAccess;

class InviteUserRequest extends TenantRequest
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
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'email.required' => __('api.enter_the_email'),
        ];
    }
}
