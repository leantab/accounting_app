<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return MobileAccess::managesCustomer($this->user());
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $actor */
        $actor = $this->user();
        $editing = $this->route('id') !== null;
        $rate = ['nullable', 'numeric', 'min:0', 'max:99999999.99'];

        return [
            'customer_id' => $actor->is_admin ? ['nullable', 'integer', 'exists:customers,id'] : ['exclude'],
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('id'))],
            // Only set when the user is created; afterwards each person resets their own through "forgot password".
            'password' => $editing ? ['exclude'] : ['required', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'user_role_id' => ['nullable', 'integer', 'exists:user_roles,id'],
            'is_active' => ['required', 'boolean'],
            'is_admin' => $actor->is_admin ? ['sometimes', 'boolean'] : ['exclude'],
            'fixed_daily_rate' => $rate,
            'fixed_monthly_rate' => $rate,
            'individual_rates' => ['sometimes', 'array'],
            'individual_rates.*' => $rate,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('api.enter_the_name'),
            'lastname.required' => __('api.enter_the_last_name'),
            'email.required' => __('api.enter_the_email'),
            'email.email' => __('api.enter_valid_email'),
            'email.unique' => 'Ya existe un usuario con ese email.',
            'password.required' => __('api.enter_a_password'),
            'password.min' => __('api.password_too_short'),
            'numeric' => __('api.enter_a_number'),
            'min' => 'El valor no puede ser negativo.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userAttributes(): array
    {
        return $this->safe()->except(['individual_rates']);
    }

    /**
     * Rates keyed by item type id; a null rate removes the custom rate.
     *
     * @return array<int, float|null>|null
     */
    public function individualRates(): ?array
    {
        if (! $this->has('individual_rates')) {
            return null;
        }

        $rates = [];

        foreach ($this->validated('individual_rates', []) as $typeId => $rate) {
            $rates[(int) $typeId] = blank($rate) ? null : (float) $rate;
        }

        return $rates;
    }
}
