<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Ingresá tu email.',
            'email.email' => 'Ingresá un email válido.',
            'password.required' => 'Ingresá tu contraseña.',
            'device_name.required' => 'Falta el nombre del dispositivo.',
        ];
    }

    public function email(): string
    {
        return Str::lower($this->string('email')->toString());
    }

    public function throttleKey(): string
    {
        return Str::transliterate($this->email().'|'.$this->ip());
    }
}
